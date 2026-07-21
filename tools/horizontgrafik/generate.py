"""Erzeugt eine Horizontgrafik (PNG) fuer einen Standort im selben Stil wie
die bestehenden `standort_fotos`-Eintraege der Kategorie 'horizontgrafik'.

Zeigt den Sonnenverlauf am 12. August 2026 (partielle Sonnenfinsternis,
Groesse/Maximumzeit als Konstanten fuer die Region Vordertaunus/Hintertaunus/
Wetterau uebernommen, siehe ECLIPSE_* unten) vor dem aus SRTM-Hoehendaten
berechneten Gelaendehorizont.

Nutzung:
    python3 generate.py --lat 50.31313 --lon 8.578405 --name "Parkplatz Am Schlick, Wehrheim" \
        --hoehe 360 --tier 2 --out wehrheim.png

Ohne --hoehe wird die SRTM-Hoehe am Standort selbst verwendet.
"""
import argparse
import sys

import matplotlib
import numpy as np

matplotlib.use("Agg")
import matplotlib.pyplot as plt
from matplotlib.colors import LinearSegmentedColormap
from matplotlib.patches import Ellipse, FancyBboxPatch

from horizontprofil import fetch_elevations, horizon_profile
from sonnenstand import sun_altaz

# Diese vier Werte sind fuer alle bisherigen Standorte des Projekts (alle im
# Umkreis von ~50 km um Bad Homburg) praktisch identisch und wurden aus den
# bestehenden Grafiken uebernommen statt pro Standort neu aus einer
# Mond-Ephemeride berechnet zu werden.
ECLIPSE_MAX_LABEL = "20:13"
ECLIPSE_MAX_H, ECLIPSE_MAX_M = 18, 13  # UTC
ECLIPSE_MAGNITUDE_PCT = 88
TIME_MARKS = [(17, 30, "19:30"), (17, 45, "19:45"), (18, 0, "20:00"), (18, 30, "20:30")]  # UTC, Label CEST

SKY_TOP = (108, 122, 158)
SKY_MID = (196, 173, 183)
SKY_BOTTOM = (243, 205, 153)
GOLD = "#f4c430"
MOON_NAVY = "#141a35"
GREEN_BANNER = "#2e7d4f"
ORANGE_BANNER = "#b35927"
TERRAIN_BLACK = "#191919"
CREAM_BOX = "#fdf8ec"
MAROON = "#5c1a1a"


def sky_gradient_cmap():
    top = tuple(c / 255 for c in SKY_TOP)
    mid = tuple(c / 255 for c in SKY_MID)
    bottom = tuple(c / 255 for c in SKY_BOTTOM)
    return LinearSegmentedColormap.from_list("dusk", [bottom, mid, top])


def sun_path(lat, lon, minute_range_utc):
    times, alts, azs = [], [], []
    for h, m in minute_range_utc:
        alt, az, _ = sun_altaz(2026, 8, 12, h, m, 0, lat, lon)
        times.append((h, m))
        alts.append(alt)
        azs.append(az)
    return np.array(azs), np.array(alts)


def minute_seq(h0, m0, h1, m1):
    t0, t1 = h0 * 60 + m0, h1 * 60 + m1
    return [(t // 60, t % 60) for t in range(t0, t1 + 1)]


def find_horizon_crossing(az_path, alt_path, az_hz, hz):
    hz_at_path = np.interp(az_path, az_hz, hz)
    diff = alt_path - hz_at_path
    above = diff > 0
    for i in range(1, len(diff)):
        if above[i - 1] and not above[i]:
            f = diff[i - 1] / (diff[i - 1] - diff[i])
            alt_c = alt_path[i - 1] + f * (alt_path[i] - alt_path[i - 1])
            az_c = az_path[i - 1] + f * (az_path[i] - az_path[i - 1])
            return az_c, alt_c
    return None


def render(lat, lon, name, hoehe_m, vom_turm, tier, out_path, az_range=(270, 300)):
    print("Berechne Horizontprofil ...", file=sys.stderr)
    az_hz, hz = horizon_profile(lat, lon, az_start=az_range[0] - 5, az_end=az_range[1] + 5, az_step=0.5)

    full_range = minute_seq(17, 15, 18, 40)
    az_path, alt_path = sun_path(lat, lon, full_range)

    crossing = find_horizon_crossing(az_path, alt_path, az_hz, hz)

    max_alt, max_az, _ = sun_altaz(2026, 8, 12, ECLIPSE_MAX_H, ECLIPSE_MAX_M, 0, lat, lon)

    fig, ax = plt.subplots(figsize=(12.24, 7.44), dpi=100)

    gradient = np.linspace(0, 1, 256).reshape(-1, 1)
    ax.imshow(
        gradient,
        extent=[az_range[0], az_range[1], 0, 14],
        aspect="auto",
        cmap=sky_gradient_cmap(),
        origin="lower",
        zorder=0,
    )

    ax.fill_between(az_hz, 0, hz, color=TERRAIN_BLACK, zorder=2, linewidth=0)

    mask = (az_path >= az_range[0] - 1) & (az_path <= az_range[1] + 1) & (alt_path >= -0.5) & (alt_path <= 14.5)
    ax.plot(az_path[mask], alt_path[mask], color=GOLD, linewidth=5, zorder=3, solid_capstyle="round")

    for h, m, label in TIME_MARKS:
        alt, az, _ = sun_altaz(2026, 8, 12, h, m, 0, lat, lon)
        if az_range[0] <= az <= az_range[1]:
            ax.plot(az, alt, "o", color=MAROON, markersize=9, zorder=4)
            ax.annotate(
                label,
                (az, alt),
                textcoords="offset points",
                xytext=(8, 10),
                color=MAROON,
                fontsize=13,
                fontweight="bold",
            )

    box_x = max_az + 1.1
    ax.add_patch(
        FancyBboxPatch(
            (box_x, max_alt - 1.05),
            5.6,
            2.1,
            boxstyle="round,pad=0.05,rounding_size=0.25",
            linewidth=1,
            edgecolor="#c9bfa0",
            facecolor=CREAM_BOX,
            zorder=6,
        )
    )
    ax.text(
        box_x + 0.3, max_alt + 0.45, f"Maximum ~{ECLIPSE_MAX_LABEL}",
        fontsize=14, fontweight="bold", color="#1a1a1a", zorder=7, va="center",
    )
    ax.text(
        box_x + 0.3, max_alt - 0.45, f"{ECLIPSE_MAGNITUDE_PCT} % · {max_alt:.1f}°",
        fontsize=14, fontweight="bold", color="#1a1a1a", zorder=7, va="center",
    )

    if crossing is None:
        banner_text = "Sonne bis zum Sonnenuntergang frei sichtbar"
        banner_color = GREEN_BANNER
    else:
        _, alt_c = crossing
        banner_text = f"Sonne verschwindet bei ~{alt_c:.1f}° Höhe hinter dem Gelände"
        banner_color = ORANGE_BANNER

    banner_w = 0.62 * (az_range[1] - az_range[0])
    banner_x = az_range[0] + 0.06 * (az_range[1] - az_range[0])
    ax.add_patch(
        FancyBboxPatch(
            (banner_x, 12.55),
            banner_w,
            1.15,
            boxstyle="round,pad=0.05,rounding_size=0.35",
            facecolor=banner_color,
            edgecolor="none",
            zorder=6,
        )
    )
    ax.text(
        banner_x + banner_w / 2, 13.1, banner_text,
        fontsize=15, fontweight="bold", color="white", ha="center", va="center", zorder=7,
    )

    ax.set_xlim(az_range)
    ax.set_ylim(0, 14)
    ax.set_xticks(range(az_range[0], az_range[1] + 1, 5))
    ax.set_yticks(range(0, 15, 2))
    ax.set_xlabel("Azimut (°)  –  Himmelsrichtung", fontsize=13)
    ax.set_ylabel("Höhe über Horizont (°)", fontsize=13)
    ax.tick_params(labelsize=12)

    compass = {270: "W", 292.5: "WNW", 315: "NW"}
    for az_c, label in compass.items():
        if az_range[0] <= az_c <= az_range[1]:
            ax.annotate(
                label, (az_c, 0), xycoords=("data", "axes fraction"),
                xytext=(0, -34), textcoords="offset points",
                ha="center", fontsize=11, color="#666666",
            )

    height_part = f"{hoehe_m:.0f} m" if hoehe_m is not None else "Höhe unbekannt"
    if vom_turm:
        height_part += " · vom Turm"
    ax.set_title(f"{name}  ({height_part})   —   Tier {tier}", fontsize=19, fontweight="bold", pad=14)

    fig.tight_layout()

    # Sonne/Mond erst jetzt zeichnen, nach dem endgueltigen Layout: die Achse hat
    # ungleiche Grad-pro-Pixel-Skalierung in x (Azimut, meist 30-50 Grad Spannweite)
    # und y (Hoehe, 0-14 Grad) -- ein Circle-Patch mit gleichem Radius in
    # Datenkoordinaten erscheint deshalb als Ellipse. Stattdessen Radius/Versatz in
    # Pixeln festlegen und erst dann achsenweise in Grad umrechnen, damit auf dem
    # Bildschirm ein echter Kreis entsteht.
    fig.canvas.draw()
    p0 = ax.transData.transform((0, 0))
    px = ax.transData.transform((1, 0))
    py = ax.transData.transform((0, 1))
    px_per_deg_x = abs(px[0] - p0[0])
    px_per_deg_y = abs(py[1] - p0[1])

    sun_r_px = 18
    rx_deg = sun_r_px / px_per_deg_x
    ry_deg = sun_r_px / px_per_deg_y
    moon_dx_px = sun_r_px * 2 * (1 - ECLIPSE_MAGNITUDE_PCT / 100) * 1.35
    moon_center = (
        max_az + (moon_dx_px * 0.15) / px_per_deg_x,
        max_alt + (moon_dx_px * 0.55) / px_per_deg_y,
    )
    ax.add_patch(Ellipse((max_az, max_alt), 2 * rx_deg, 2 * ry_deg, color=GOLD, zorder=5))
    ax.add_patch(Ellipse(moon_center, 2 * rx_deg, 2 * ry_deg, color=MOON_NAVY, zorder=6))

    fig.savefig(out_path, dpi=100)
    print(f"Geschrieben: {out_path}", file=sys.stderr)
    if crossing is not None:
        print(f"Horizont blockiert die Sonne ab ~{crossing[1]:.2f} Grad Hoehe", file=sys.stderr)
    else:
        print("Sonne bis zum Sonnenuntergang frei sichtbar", file=sys.stderr)


def main():
    p = argparse.ArgumentParser()
    p.add_argument("--lat", type=float, required=True)
    p.add_argument("--lon", type=float, required=True)
    p.add_argument("--name", required=True)
    p.add_argument("--hoehe", type=float, default=None, help="Hoehe in m, falls bekannt (sonst SRTM)")
    p.add_argument("--vom-turm", action="store_true")
    p.add_argument("--tier", type=int, required=True)
    p.add_argument("--out", required=True)
    args = p.parse_args()

    hoehe = args.hoehe
    if hoehe is None:
        hoehe = fetch_elevations([(args.lat, args.lon)])[0]

    render(args.lat, args.lon, args.name, hoehe, args.vom_turm, args.tier, args.out)


if __name__ == "__main__":
    main()

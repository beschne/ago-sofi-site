"""Horizontgrafik aus einem echten (kalibrierten) Panoramafoto statt aus
SRTM-Daten -- fuer Standorte, an denen bereits eine Vor-Ort-Sichthoehen-
Vermessung vorliegt (z. B. per Referenzpunkten mit bekanntem Azimut/Winkel
markiert). Rendert im selben Stil wie generate.py (Titel, Zeitmarken,
Finsternis-Maximum, Banner), aber mit dem Foto als Hintergrund statt
Himmelsverlauf + Gelaendesilhouette.
"""
import sys

import matplotlib
import numpy as np

matplotlib.use("Agg")
import matplotlib.pyplot as plt
from matplotlib.patches import Circle, FancyBboxPatch

from sonnenstand import sun_altaz

ECLIPSE_MAX_LABEL = "20:13"
ECLIPSE_MAX_H, ECLIPSE_MAX_M = 18, 13  # UTC
ECLIPSE_MAGNITUDE_PCT = 88
TIME_MARKS = [(17, 30, "19:30"), (17, 45, "19:45"), (18, 0, "20:00"), (18, 30, "20:30")]

GOLD = "#f4c430"
MOON_NAVY = "#141a35"
ORANGE_BANNER = "#b35927"
CREAM_BOX = "#fdf8ec"
MAROON = "#5c1a1a"


def minute_seq(h0, m0, h1, m1):
    t0, t1 = h0 * 60 + m0, h1 * 60 + m1
    return [(t // 60, t % 60) for t in range(t0, t1 + 1)]


def render(
    lat, lon, name, hoehe_m, tier, photo_path, az_to_col, row_to_alt, crop_box,
    az_range, alt_range, banner_text, horizon_hinweis, out_path,
):
    """az_to_col: (slope, intercept) fuer col = slope*az+intercept.
    row_to_alt: (slope, intercept) fuer alt = slope*row+intercept.
    Beides kalibriert an den echten Referenzpunkten (Pink-Linien im Original)."""
    from PIL import Image

    img = Image.open(photo_path).convert("RGB")
    left, top, right, bottom = crop_box
    crop = img.crop((left, top, right, bottom))

    az_s, az_i = az_to_col
    alt_s, alt_i = row_to_alt
    extent_left = (left - az_i) / az_s
    extent_right = (right - az_i) / az_s
    extent_top = alt_s * top + alt_i
    extent_bottom = alt_s * bottom + alt_i

    fig, ax = plt.subplots(figsize=(12.24, 7.44), dpi=100)
    ax.imshow(np.array(crop), extent=[extent_left, extent_right, extent_bottom, extent_top], aspect="auto",
              zorder=0, interpolation="lanczos")

    full_range = minute_seq(17, 15, 18, 40)
    az_path, alt_path = [], []
    for h, m in full_range:
        alt, az, _ = sun_altaz(2026, 8, 12, h, m, 0, lat, lon)
        az_path.append(az)
        alt_path.append(alt)
    az_path, alt_path = np.array(az_path), np.array(alt_path)
    mask = (az_path >= az_range[0] - 1) & (az_path <= az_range[1] + 1) & (alt_path >= alt_range[0] - 1) & (alt_path <= alt_range[1] + 1)
    ax.plot(az_path[mask], alt_path[mask], color=GOLD, linewidth=4, zorder=3, solid_capstyle="round", alpha=0.9,
            linestyle=(0, (1, 1.2)))

    for h, m, label in TIME_MARKS:
        alt, az, _ = sun_altaz(2026, 8, 12, h, m, 0, lat, lon)
        if az_range[0] <= az <= az_range[1]:
            ax.plot(az, alt, "o", color=MAROON, markersize=8, zorder=4)
            ax.annotate(label, (az, alt), textcoords="offset points", xytext=(7, 8), color=MAROON,
                        fontsize=12, fontweight="bold")

    max_alt, max_az, _ = sun_altaz(2026, 8, 12, ECLIPSE_MAX_H, ECLIPSE_MAX_M, 0, lat, lon)
    sun_r = (alt_range[1] - alt_range[0]) * 0.03
    moon_dx = sun_r * 2 * (1 - ECLIPSE_MAGNITUDE_PCT / 100) * 1.35
    ax.add_patch(Circle((max_az, max_alt), sun_r, color=GOLD, zorder=5))
    ax.add_patch(Circle((max_az + moon_dx * 0.15, max_alt + moon_dx * 0.55), sun_r, color=MOON_NAVY, zorder=6))

    box_x = max_az + 1.0
    box_w = (az_range[1] - az_range[0]) * 0.19
    box_h = (alt_range[1] - alt_range[0]) * 0.15
    if box_x + box_w > az_range[1]:
        box_x = max_az - 1.0 - box_w
    ax.add_patch(FancyBboxPatch((box_x, max_alt - box_h / 2), box_w, box_h,
                                 boxstyle="round,pad=0.05,rounding_size=0.2", linewidth=1,
                                 edgecolor="#c9bfa0", facecolor=CREAM_BOX, zorder=6))
    ax.text(box_x + box_w * 0.08, max_alt + box_h * 0.2, f"Maximum ~{ECLIPSE_MAX_LABEL}", fontsize=13,
            fontweight="bold", color="#1a1a1a", zorder=7, va="center")
    ax.text(box_x + box_w * 0.08, max_alt - box_h * 0.2, f"{ECLIPSE_MAGNITUDE_PCT} % · {max_alt:.1f}°", fontsize=13,
            fontweight="bold", color="#1a1a1a", zorder=7, va="center")

    banner_w = 0.85 * (az_range[1] - az_range[0])
    banner_x = az_range[0] + 0.5 * (az_range[1] - az_range[0] - banner_w)
    banner_h = (alt_range[1] - alt_range[0]) * 0.14
    banner_y = alt_range[1] - banner_h * 1.4
    ax.add_patch(FancyBboxPatch((banner_x, banner_y), banner_w, banner_h,
                                 boxstyle="round,pad=0.05,rounding_size=0.3", facecolor=ORANGE_BANNER,
                                 edgecolor="none", zorder=6))
    ax.text(banner_x + banner_w / 2, banner_y + banner_h / 2, banner_text, fontsize=14, fontweight="bold",
            color="white", ha="center", va="center", zorder=7)

    if horizon_hinweis:
        ax.text(az_range[0] + 0.3, alt_range[0] + 0.3, horizon_hinweis, fontsize=10, color="white",
                fontweight="bold", zorder=7, va="bottom",
                path_effects=[__import__("matplotlib.patheffects", fromlist=["withStroke"]).withStroke(linewidth=3, foreground="#00000099")])

    ax.set_xlim(az_range)
    ax.set_ylim(alt_range)
    ax.set_xticks(range(int(az_range[0]), int(az_range[1]) + 1, 5))
    ax.set_yticks(range(int(alt_range[0]), int(alt_range[1]) + 1, 2))
    ax.set_xlabel("Azimut (°)  –  Himmelsrichtung", fontsize=13)
    ax.set_ylabel("Höhe über Horizont (°)", fontsize=13)
    ax.tick_params(labelsize=12)

    compass = {270: "W", 292.5: "WNW", 315: "NW"}
    for az_c, label in compass.items():
        if az_range[0] <= az_c <= az_range[1]:
            ax.annotate(label, (az_c, alt_range[0]), xycoords="data", xytext=(0, -34),
                        textcoords="offset points", ha="center", fontsize=11, color="#666666")

    ax.set_title(f"{name}  ({hoehe_m:.0f} m)   —   Tier {tier}", fontsize=19, fontweight="bold", pad=14)

    fig.tight_layout()
    fig.savefig(out_path, dpi=100)
    print(f"Geschrieben: {out_path}", file=sys.stderr)

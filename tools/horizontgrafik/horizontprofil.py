"""Horizontprofil aus SRTM-Höhendaten (opentopodata.org, oeffentliche API,
kein Key noetig). Fuer jeden Azimut wird entlang eines Strahls in
zunehmender Entfernung die groesste scheinbare Hoehe ueber dem Horizont
gesucht (inkl. Erdkruemmung + Standardrefraktion)."""
import time

import numpy as np
import requests

EARTH_R_M = 6371000.0
REFRACTION_K = 0.13  # Standard-Refraktionskoeffizient fuer terrestrische Sichtlinien
R_EFF_M = EARTH_R_M / (1 - REFRACTION_K)

DISTANCES_KM = [0.5, 0.75, 1, 1.5, 2, 3, 4, 5, 7, 10, 13, 16, 20, 25, 30, 40]


def destination(lat, lon, distance_km, bearing_deg):
    R = 6371.0
    lat1, lon1 = np.radians(lat), np.radians(lon)
    brg = np.radians(bearing_deg)
    delta = distance_km / R
    lat2 = np.arcsin(np.sin(lat1) * np.cos(delta) + np.cos(lat1) * np.sin(delta) * np.cos(brg))
    lon2 = lon1 + np.arctan2(
        np.sin(brg) * np.sin(delta) * np.cos(lat1), np.cos(delta) - np.sin(lat1) * np.sin(lat2)
    )
    return np.degrees(lat2), np.degrees(lon2)


def fetch_elevations(points, dataset="srtm30m"):
    """points: Liste von (lat, lon). Gibt Liste von Hoehen in Metern zurueck."""
    out = []
    for i in range(0, len(points), 100):
        chunk = points[i : i + 100]
        locs = "|".join(f"{lat:.6f},{lon:.6f}" for lat, lon in chunk)
        resp = requests.get(
            f"https://api.opentopodata.org/v1/{dataset}", params={"locations": locs}, timeout=30
        )
        resp.raise_for_status()
        data = resp.json()
        for r in data["results"]:
            out.append(r["elevation"])
        if i + 100 < len(points):
            time.sleep(1.1)
    return out


def horizon_profile(lat, lon, eye_height_m=1.6, az_start=260, az_end=310, az_step=1.0):
    """Liefert (azimute, horizont_winkel_grad) fuer den angegebenen Azimutbereich.

    Die Beobachterhoehe wird bewusst aus demselben SRTM-Datensatz wie die
    Zielpunkte entlang der Sichtstrahlen bezogen (statt aus einer separat
    vermessenen Hoehe) -- so kuerzt sich der systematische SRTM-Versatz
    zwischen Beobachter- und Zielpunkt weitgehend heraus, statt als
    Scheinobstruktion durch Rasterrauschen im Nahbereich aufzutauchen."""
    azimuths = np.arange(az_start, az_end + 1e-9, az_step)
    ray_points = [(lat, lon)]
    for az in azimuths:
        for d in DISTANCES_KM:
            ray_points.append(destination(lat, lon, d, az))

    elevations = fetch_elevations(ray_points)
    elevations = np.array(elevations, dtype=float)
    elevations = np.nan_to_num(elevations, nan=0.0)
    obs_srtm = elevations[0]
    elevations = elevations[1:]
    n_d = len(DISTANCES_KM)
    elevations = elevations.reshape(len(azimuths), n_d)

    obs_elev = obs_srtm + eye_height_m
    horizon_deg = np.zeros(len(azimuths))
    for i, az in enumerate(azimuths):
        d_m = np.array(DISTANCES_KM) * 1000.0
        drop_m = d_m**2 / (2 * R_EFF_M)
        angle_rad = np.arctan2(elevations[i] - obs_elev - drop_m, d_m)
        horizon_deg[i] = np.degrees(np.max(angle_rad))

    horizon_deg = np.clip(horizon_deg, 0.0, None)
    return azimuths, horizon_deg

"""Sonnenposition (Alt/Az) nach dem 'low precision'-Algorithmus aus Meeus,
Astronomical Algorithms, Kap. 25 (Genauigkeit ~0.01 Grad) plus Refraktion
nach Bennett. Reicht für die Horizontgrafiken locker aus, ohne dass eine
JPL-Ephemeride heruntergeladen werden muss.
"""
import numpy as np


def julian_day(y, mo, d, h, mi, s):
    if mo <= 2:
        y -= 1
        mo += 12
    a = y // 100
    b = 2 - a + a // 4
    jd = int(365.25 * (y + 4716)) + int(30.6001 * (mo + 1)) + d + b - 1524.5
    jd += (h + mi / 60 + s / 3600) / 24
    return jd


def sun_ra_dec(jd):
    t = (jd - 2451545.0) / 36525.0
    l0 = (280.46646 + 36000.76983 * t + 0.0003032 * t**2) % 360
    m = (357.52911 + 35999.05029 * t - 0.0001537 * t**2) % 360
    mr = np.radians(m)
    c = (
        (1.914602 - 0.004817 * t - 0.000014 * t**2) * np.sin(mr)
        + (0.019993 - 0.000101 * t) * np.sin(2 * mr)
        + 0.000289 * np.sin(3 * mr)
    )
    true_long = l0 + c
    omega = 125.04 - 1934.136 * t
    app_long = true_long - 0.00569 - 0.00478 * np.sin(np.radians(omega))
    eps0 = 23 + 26 / 60 + 21.448 / 3600 - (46.8150 * t + 0.00059 * t**2 - 0.001813 * t**3) / 3600
    eps = eps0 + 0.00256 * np.cos(np.radians(omega))
    lam = np.radians(app_long)
    epsr = np.radians(eps)
    ra = np.degrees(np.arctan2(np.cos(epsr) * np.sin(lam), np.cos(lam))) % 360
    dec = np.degrees(np.arcsin(np.sin(epsr) * np.sin(lam)))
    return ra, dec


def gmst_deg(jd):
    t = (jd - 2451545.0) / 36525.0
    gmst = 280.46061837 + 360.98564736629 * (jd - 2451545.0) + 0.000387933 * t**2 - t**3 / 38710000.0
    return gmst % 360


def refraction_deg(alt_true_deg):
    """Bennett-Formel, wahre -> scheinbare Höhe (Standardbedingungen)."""
    h = np.asarray(alt_true_deg, dtype=float)
    r = 1.0 / np.tan(np.radians(h + 7.31 / (h + 4.4)))
    r_arcmin = r - 0.06 * np.sin(np.radians(14.7 * r + 13))
    return r_arcmin / 60.0


def sun_altaz(y, mo, d, h, mi, s, lat, lon):
    """lat/lon in Grad (Ost positiv), Zeit in UTC. Gibt (alt_scheinbar,
    azimut, alt_wahr) in Grad zurück, Azimut von Nord über Ost (0-360)."""
    jd = julian_day(y, mo, d, h, mi, s)
    ra, dec = sun_ra_dec(jd)
    gmst = gmst_deg(jd)
    lst = (gmst + lon) % 360
    ha = ((lst - ra + 180) % 360) - 180
    har = np.radians(ha)
    latr = np.radians(lat)
    decr = np.radians(dec)
    sin_alt = np.sin(latr) * np.sin(decr) + np.cos(latr) * np.cos(decr) * np.cos(har)
    alt_true = np.degrees(np.arcsin(sin_alt))
    az = np.degrees(np.arctan2(np.sin(har), np.cos(har) * np.sin(latr) - np.tan(decr) * np.cos(latr)))
    az = (az + 180) % 360
    alt_app = alt_true + refraction_deg(alt_true)
    return alt_app, az, alt_true

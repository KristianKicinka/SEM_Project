"""
 * @file filter_manager.py
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2025
"""

import os
import re
from datetime import datetime

# Nastavenie ciest k adresárom a súborom
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
PROJECT_ROOT = os.path.abspath(os.path.join(SCRIPT_DIR, ".."))
BLACKLIST_DIR = os.path.join(SCRIPT_DIR, "black_lists")
WHITELIST_FILE = os.path.join(SCRIPT_DIR, "white_lists", "sni_whitelist.txt")
LOG_DIR = os.path.join(PROJECT_ROOT, "storage", "logs", "filter_logs")
os.makedirs(LOG_DIR, exist_ok=True)

# Načítanie whitelistu (SNI ktoré majú prioritu a nebudú nikdy filtrované)
with open(WHITELIST_FILE, "r") as f:
    WHITELIST = set(line.strip().lower() for line in f if line.strip())

# Príprava dátových štruktúr pre blacklisty
BLACKLIST_FULL = set()        # úplné zhody domén (napr. analytics.example.com)
BLACKLIST_KEYWORDS = set()    # kľúčové slová (napr. 'ads', 'track')
REGEX_PATTERNS = []           # regulárne výrazy pre pokročilé matchovanie

# Načítanie blacklist súborov podľa kategórií (ads, analytics, atď.)
for fname in ["ads.txt", "analytics.txt", "cdn.txt", "shared_api.txt"]:
    path = os.path.join(BLACKLIST_DIR, fname)
    with open(path, "r") as f:
        for line in f:
            keyword = line.strip().lower()
            if "." in keyword:
                BLACKLIST_FULL.add(keyword)  # priamo zhodné domény
            else:
                BLACKLIST_KEYWORDS.add(keyword)  # podreťazce v SNI častiach

# Načítanie pôvodného (legacy) blacklistu – domén na presnú zhodu
legacy_file = os.path.join(BLACKLIST_DIR, "domain_black_list.txt")
if os.path.exists(legacy_file):
    with open(legacy_file, "r") as f:
        for line in f:
            domain = line.strip().lower()
            if domain:
                BLACKLIST_FULL.add(domain)

# Načítanie regex pravidiel z regex_blacklist.txt
regex_file = os.path.join(BLACKLIST_DIR, "regex_blacklist.txt")
with open(regex_file, "r") as f:
    REGEX_PATTERNS = [re.compile(line.strip(), re.IGNORECASE) for line in f if line.strip()]

# Cesta k súboru s logmi rozhodnutí (čo bolo filtrované a prečo)
log_file_path = os.path.join(LOG_DIR, f"filter_log_{datetime.now().strftime('%Y%m%d_%H%M%S')}.log")

def is_whitelisted(sni):
    """Overí, či je SNI na whitelist zozname."""
    return sni and sni.lower() in WHITELIST

def is_blacklisted(sni):
    """Overí, či daný SNI spĺňa niektoré z blacklist podmienok."""
    if not sni:
        return False
    sni = sni.lower()

    # Úplná zhoda (full domain match)
    if sni in BLACKLIST_FULL:
        log_decision(sni, True, "full_match")
        return True

    # Porovnanie s kľúčovými slovami (napr. 'ads', 'tracking')
    for part in sni.split('.'):
        if part in BLACKLIST_KEYWORDS:
            log_decision(sni, True, f"keyword:{part}")
            return True

    # Porovnanie cez regex vzory (napr. .*\.ads\..*)
    for pattern in REGEX_PATTERNS:
        if pattern.match(sni):
            log_decision(sni, True, f"regex:{pattern.pattern}")
            return True

    return False

def should_filter(sni):
    """Rozhodne, či má byť daný SNI odfiltrovaný (blacklist > whitelist)."""
    if is_whitelisted(sni):
        log_decision(sni, False, "whitelisted")
        return False
    return is_blacklisted(sni)

def log_decision(sni, filtered, reason):
    """Zaloguje dôvod, prečo bol SNI odfiltrovaný alebo povolený."""
    with open(log_file_path, "a") as log:
        log.write(f"{datetime.now().isoformat()} | {sni} | {'FILTERED' if filtered else 'ALLOWED'} | reason={reason}\n")

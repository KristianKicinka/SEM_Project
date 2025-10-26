"""
 * @file filter_manager.py
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2025
"""

import os
import re
from datetime import datetime

# Set paths to directories and files
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
PROJECT_ROOT = os.path.abspath(os.path.join(SCRIPT_DIR, ".."))
BLACKLIST_DIR = os.path.join(SCRIPT_DIR, "black_lists")
WHITELIST_FILE = os.path.join(SCRIPT_DIR, "white_lists", "sni_whitelist.txt")
LOG_DIR = os.path.join(PROJECT_ROOT, "storage", "logs", "filter_logs")
os.makedirs(LOG_DIR, exist_ok=True)

# Load whitelist (SNI that have priority and will never be filtered)
with open(WHITELIST_FILE, "r") as f:
    WHITELIST = set(line.strip().lower() for line in f if line.strip())

# Prepare data structures for blacklists
BLACKLIST_FULL = set()        # exact domain matches (e.g. analytics.example.com)
BLACKLIST_KEYWORDS = set()    # keywords (e.g. 'ads', 'track')
REGEX_PATTERNS = []           # regular expressions for advanced matching

# Load blacklist files by categories (ads, analytics, etc.)
for fname in ["ads.txt", "analytics.txt", "cdn.txt", "shared_api.txt"]:
    path = os.path.join(BLACKLIST_DIR, fname)
    with open(path, "r") as f:
        for line in f:
            keyword = line.strip().lower()
            if "." in keyword:
                BLACKLIST_FULL.add(keyword)  # direct domain matches
            else:
                BLACKLIST_KEYWORDS.add(keyword)  # substrings in SNI parts

# Load original (legacy) blacklist - domains for exact match
legacy_file = os.path.join(BLACKLIST_DIR, "domain_black_list.txt")
if os.path.exists(legacy_file):
    with open(legacy_file, "r") as f:
        for line in f:
            domain = line.strip().lower()
            if domain:
                BLACKLIST_FULL.add(domain)

# Load regex rules from regex_blacklist.txt
regex_file = os.path.join(BLACKLIST_DIR, "regex_blacklist.txt")
with open(regex_file, "r") as f:
    REGEX_PATTERNS = [re.compile(line.strip(), re.IGNORECASE) for line in f if line.strip()]

# Path to decision log file (what was filtered and why)
log_file_path = os.path.join(LOG_DIR, f"filter_log_{datetime.now().strftime('%Y%m%d_%H%M%S')}.log")

def is_whitelisted(sni):
    """Check if SNI is on whitelist."""
    return sni and sni.lower() in WHITELIST

def is_blacklisted(sni):
    """Check if given SNI meets any blacklist conditions."""
    if not sni:
        return False
    sni = sni.lower()

    # Exact match (full domain match)
    if sni in BLACKLIST_FULL:
        log_decision(sni, True, "full_match")
        return True

    # Compare with keywords (e.g. 'ads', 'tracking')
    for part in sni.split('.'):
        if part in BLACKLIST_KEYWORDS:
            log_decision(sni, True, f"keyword:{part}")
            return True

    # Compare via regex patterns (e.g. .*\.ads\..*)
    for pattern in REGEX_PATTERNS:
        if pattern.match(sni):
            log_decision(sni, True, f"regex:{pattern.pattern}")
            return True

    return False

def should_filter(sni):
    """Decide whether given SNI should be filtered (blacklist > whitelist)."""
    if is_whitelisted(sni):
        log_decision(sni, False, "whitelisted")
        return False
    return is_blacklisted(sni)

def get_sni_flag(sni):
    """
    Get flag for SNI based on blacklist categories.
    Returns flag string or None if not blacklisted.
    """
    if not sni:
        return None
    
    sni_lower = sni.lower()
    
    # Load keywords from blacklist files for each category
    ads_keywords = set()
    analytics_keywords = set()
    cdn_keywords = set()
    shared_api_keywords = set()
    
    # Load ads keywords
    ads_file = os.path.join(BLACKLIST_DIR, "ads.txt")
    if os.path.exists(ads_file):
        with open(ads_file, "r") as f:
            ads_keywords = set(line.strip().lower() for line in f if line.strip())
    
    # Load analytics keywords
    analytics_file = os.path.join(BLACKLIST_DIR, "analytics.txt")
    if os.path.exists(analytics_file):
        with open(analytics_file, "r") as f:
            analytics_keywords = set(line.strip().lower() for line in f if line.strip())
    
    # Load CDN keywords
    cdn_file = os.path.join(BLACKLIST_DIR, "cdn.txt")
    if os.path.exists(cdn_file):
        with open(cdn_file, "r") as f:
            cdn_keywords = set(line.strip().lower() for line in f if line.strip())
    
    # Load shared API keywords
    shared_api_file = os.path.join(BLACKLIST_DIR, "shared_api.txt")
    if os.path.exists(shared_api_file):
        with open(shared_api_file, "r") as f:
            shared_api_keywords = set(line.strip().lower() for line in f if line.strip())
    
    # Check for shared API domains first (most specific)
    if any(keyword in sni_lower for keyword in shared_api_keywords):
        return "shared_api_communication"
    
    # Check for CDN domains
    if any(keyword in sni_lower for keyword in cdn_keywords):
        return "cdn_communication"
    
    # Check for advertisement domains
    if any(keyword in sni_lower for keyword in ads_keywords):
        return "advertisement_communication"
    
    # Check for analytics domains (least specific - contains 'google')
    if any(keyword in sni_lower for keyword in analytics_keywords):
        return "analytics_communication"
    
    # Check exact blacklist matches
    if sni_lower in BLACKLIST_FULL:
        return "blacklisted_communication"
    
    # Check regex patterns
    for pattern in REGEX_PATTERNS:
        if pattern.match(sni_lower):
            return "pattern_matched_communication"
    
    return None

def log_decision(sni, filtered, reason):
    """Log the reason why SNI was filtered or allowed."""
    with open(log_file_path, "a") as log:
        log.write(f"{datetime.now().isoformat()} | {sni} | {'FILTERED' if filtered else 'ALLOWED'} | reason={reason}\n")

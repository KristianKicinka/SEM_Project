# Kompletná dokumentácia systému hash generovania

## Obsah
1. [Úvod](#úvod)
2. [Základné hash typy](#základné-hash-typy)
3. [Custom hash typy - Prehľad](#custom-hash-typy---prehľad)
4. [Custom hash typy - Architektúra](#custom-hash-typy---architektúra)
5. [Custom hash typy - Databázová integrácia](#custom-hash-typy---databázová-integrácia)
6. [Custom hash typy - Manuálna konfigurácia](#custom-hash-typy---manuálna-konfigurácia)
7. [Custom hash typy - Dostupné polia](#custom-hash-typy---dostupné-polia)
8. [Custom hash typy - Typy generátorov](#custom-hash-typy---typy-generátorov)
9. [Custom hash typy - Príklady použitia](#custom-hash-typy---príklady-použitia)
10. [Custom hash typy - API referencie](#custom-hash-typy---api-referencie)
11. [Custom hash typy - Testovanie](#custom-hash-typy---testovanie)
12. [Custom hash typy - Riešenie problémov](#custom-hash-typy---riešenie-problémov)
13. [Custom hash typy - Bezpečnosť](#custom-hash-typy---bezpečnosť)
14. [Custom hash typy - Performance](#custom-hash-typy---performance)
15. [Custom hash typy - Najlepšie praktiky](#custom-hash-typy---najlepšie-praktiky)
16. [Python implementácia](#python-implementácia)
17. [Laravel backend](#laravel-backend)
18. [API rozhranie](#api-rozhranie)
19. [Admin panel](#admin-panel)
20. [Frontend implementácia](#frontend-implementácia)
21. [Používanie systému](#používanie-systému)

## Úvod

SEM Project je systém na analýzu mobilných aplikácií pomocou TLS fingerprintingu. Systém generuje rôzne typy hash odtlačkov z TLS handshake packetov, ktoré umožňujú identifikáciu a kategorizáciu mobilných aplikácií.

### Hlavné komponenty:
- **Python hash generátor** (`hash_generator.py`) - jadro systému
- **Laravel backend** - API a databáza
- **React frontend** - web rozhranie
- **Custom hash systém** - rozšíriteľnosť pre vlastné hash typy

## Základné hash typy

### JA3 Hash
```python
def create_JA3_hash(packet):
    """
    Generuje JA3 hash z TLS Client Hello packetu
    Formát: version,ciphers,extensions,elliptic_curves,elliptic_curve_point_formats
    """
```

**Princíp:**
- Kombinuje TLS verziu, cipher suity, extensions, elliptic curves a point formats
- Používa MD5 hash algoritmus
- Štandardný TLS fingerprinting

### JA3S Hash
```python
def create_JA3S_hash(packet):
    """
    Generuje JA3S hash z TLS Server Hello packetu
    Formát: version,cipher,extensions
    """
```

**Princíp:**
- Server-side ekvivalent JA3
- Kombinuje TLS verziu, cipher a extensions
- Používa MD5 hash algoritmus

### JA4 Hash
```python
def create_JA4_hash(packet, sni=None):
    """
    Generuje JA4 hash z TLS Client Hello packetu
    Formát: t<version>d<ciphers>h<extensions>_<sni_hash>
    """
```

**Princíp:**
- Moderný TLS fingerprinting
- Kombinuje TLS verziu, cipher suity, extensions a SNI
- Používa SHA256 hash algoritmus
- Lepšia unikátnosť ako JA3

### JA4S Hash
```python
def create_JA4S_hash(packet):
    """
    Generuje JA4S hash z TLS Server Hello packetu
    Formát: t<version>d<ciphers>h<extensions>
    """
```

**Princíp:**
- Server-side ekvivalent JA4
- Kombinuje TLS verziu, cipher a extensions
- Používa SHA256 hash algoritmus

### JA4X Hash
```python
def create_JA4X_hash(packet):
    """
    Generuje JA4X hash z TLS Certificate packetu
    Formát: t<version>d<ciphers>h<extensions>_<certificate_hash>
    """
```

**Princíp:**
- Certificate-based fingerprinting
- Kombinuje TLS verziu, cipher suity, extensions a certificate hash
- Používa SHA256 hash algoritmus
- Najvyššia unikátnosť

## Custom hash typy - Prehľad

Custom Hash Types systém umožňuje používateľom vytvárať vlastné odtlačky mobilných aplikácií pomocou rôznych atribútov packetov. Systém podporuje oba režimy - databázovú integráciu a manuálnu konfiguráciu, čo poskytuje flexibilitu pre rôzne prípady použitia.

### Kľúčové funkcie
- **Dvojitý režim**: Databázová integrácia + Manuálna konfigurácia
- **Bohatá podpora polí**: 41 dostupných polí pre fingerprinting
- **Viacero typov generátorov**: Simple TLS, Custom Algorithm, Python Script
- **Anglická dokumentácia**: Medzinárodná kompatibilita
- **Komplexné testovanie**: Kompletná testovacia sada
- **Bezpečnosť**: API key autentifikácia a validácia

### Komponenty systému
- **Laravel Backend**: Databázové úložisko a API endpointy
- **Python Hash Generator**: Jadro fingerprinting enginu
- **React Frontend**: Web rozhranie pre správu
- **Konfiguračný systém**: JSON-based manuálna konfigurácia

## Custom hash typy - Architektúra

```
┌─────────────────┐    HTTP API    ┌─────────────────┐    Python    ┌─────────────────┐
│   Laravel DB    │ ──────────────► │  Python Script │ ────────────► │  Hash Generator │
│ CustomHashTypes │                 │ dynamic_loader  │              │ hash_generator  │
└─────────────────┘                 └─────────────────┘              └─────────────────┘
```

### Štruktúra súborov
```
scripts/
├── custom_hash_generators.py      # Hlavné triedy generátorov
├── dynamic_hash_loader.py         # Databázový/Konfiguračný loader
├── hash_generator.py              # Hlavný hash generátor
├── custom_hash_config.json        # Manuálna konfigurácia
├── tests/                         # Testovacie skripty
│   ├── test_api_integration.py
│   ├── test_config_only.py
│   ├── test_custom_hash_integration.py
│   ├── test_custom_hashes.py
│   └── test_manual_config.py
└── COMPLETE_HASH_SYSTEM_DOCUMENTATION.md  # Táto dokumentácia
```

## Custom hash typy - Databázová integrácia

### Laravel Backend

#### Modely
```php
// app/Models/CustomHashType.php
class CustomHashType extends Model
{
    protected $fillable = [
        'user_id', 'name', 'display_name', 'description', 'type',
        'configuration', 'script_path', 'is_active', 'is_public', 'usage_count',
    ];
    
    protected $casts = [
        'configuration' => 'array',
        'is_active' => 'boolean',
        'is_public' => 'boolean',
        'usage_count' => 'integer',
    ];
}
```

#### API Endpointy
```php
// routes/api.php
Route::group(['middleware' => ['python']], function () {
    Route::post('/custom-hash-types/python-generator', [CustomHashTypeController::class, 'getForPythonGenerator']);
});
```

#### Controller metódy
```php
// app/Http/Controllers/CustomHashTypeController.php
public function getForPythonGenerator(Request $request)
{
    $requestedNames = $request->input('names', []);
    $query = CustomHashType::where('is_active', true);
    
    if (!empty($requestedNames)) {
        $query->whereIn('name', $requestedNames);
    }
    
    $customHashTypes = $query->select(
        'name', 'display_name', 'description', 'type', 
        'configuration', 'script_path'
    )->get();
    
    return response()->json([
        'generators' => $customHashTypes,
        'count' => count($customHashTypes)
    ]);
}
```

### Python integrácia

#### Databázový loader
```python
# scripts/dynamic_hash_loader.py
class DatabaseHashLoader:
    def load_custom_hash_types(self, custom_hash_type_names=None, use_manual_config=False):
        if use_manual_config:
            return self._load_from_manual_config(custom_hash_type_names)
        
        try:
            db_config = self._load_from_database_api(custom_hash_type_names)
            generators = {}
            for config in db_config:
                generator = self._create_generator_from_db_config(config)
                if generator:
                    generators[generator.name] = generator
            return generators
        except Exception as e:
            print(f"Error loading from database: {e}")
            return self._load_from_manual_config(custom_hash_type_names)
```

#### Hash generator integrácia
```python
# scripts/hash_generator.py
def generate_custom_hashes(packet, sni=None, custom_generators=None):
    use_manual_config = os.getenv('USE_MANUAL_CONFIG', 'false').lower() == 'true'
    db_generators = load_custom_hash_types_from_database(custom_generators, use_manual_config)
    
    if db_generators:
        results = {}
        for name, generator in db_generators.items():
            if generator.validate_packet(packet):
                hash_value = generator.generate_hash(packet, sni)
                results[f'custom_{name}'] = hash_value
        return results
```

## Custom hash typy - Manuálna konfigurácia

### Štruktúra konfiguračného súboru
```json
{
  "version": "1.0",
  "description": "Configuration for custom hash generators",
  "generators": [
    {
      "name": "CUSTOM_TLS_SIMPLE",
      "type": "simple_tls",
      "description": "Simple TLS hash based on version and cipher suite",
      "configuration": {
        "fields": ["version", "ciphers"]
      }
    },
    {
      "name": "CUSTOM_IP_HASH",
      "type": "custom_algorithm",
      "description": "Hash based on IP addresses and ports",
      "configuration": {
        "algorithm": "sha256",
        "fields": ["ip_src", "ip_dst", "port_src", "port_dst"]
      }
    }
  ]
}
```

### Režimy použitia

#### Databázový režim (Predvolený)
```bash
python3 hash_generator.py input.pcap '["CUSTOM_TLS_SIMPLE"]'
```

#### Manuálny konfiguračný režim
```bash
export USE_MANUAL_CONFIG=true
python3 hash_generator.py input.pcap '["CUSTOM_TLS_SIMPLE"]'
```

#### Python API
```python
# Databázový režim
generators = load_custom_hash_types_from_database(["CUSTOM_TLS_SIMPLE"])

# Manuálny režim
generators = load_custom_hash_types_from_database(
    ["CUSTOM_TLS_SIMPLE"], 
    use_manual_config=True
)
```

## Custom hash typy - Dostupné polia

### TLS polia (11 polí)
- `version` - TLS verzia
- `ciphers` - Cipher suity
- `extensions` - TLS extensions
- `compression_methods` - Kompresné metódy
- `supported_versions` - Podporované TLS verzie
- `signature_algorithms` - Algoritmy podpisov
- `elliptic_curves` - Eliptické krivky
- `ec_point_formats` - EC point formáty
- `alpn_protocols` - ALPN protokoly
- `sni` - Server Name Indicator
- `timestamp` - Časová značka packetu

### Sieťové polia (30 polí)
- **IP vrstva**: `ip_src`, `ip_dst`, `ip_proto`, `ip_ttl`, `ip_tos`, `ip_flags`, `ip_id`, `ip_len`
- **TCP vrstva**: `port_src`, `port_dst`, `tcp_flags`, `tcp_seq`, `tcp_ack`, `tcp_window`, `tcp_urgptr`
- **UDP vrstva**: `udp_sport`, `udp_dport`, `udp_len`
- **TLS vrstva**: `tls_version`, `tls_content_type`, `tls_length`
- **Ethernet vrstva**: `eth_src`, `eth_dst`, `eth_type`
- **Vlastné polia**: `packet_hash`, `payload_size`, `layer_count`, `timestamp`, `packet_size`, `sni`

## Custom hash typy - Typy generátorov

### 1. Simple TLS Hash Generator

```json
{
  "name": "CUSTOM_TLS_SIMPLE",
  "type": "simple_tls",
  "description": "Simple TLS hash based on version and cipher suite",
  "configuration": {
    "fields": ["version", "ciphers", "extensions"]
  }
}
```

**Dostupné polia**: Všetky TLS polia uvedené vyššie
**Hash algoritmus**: MD5 (pevný)
**Prípad použitia**: TLS-špecifický fingerprinting

### 2. Custom Algorithm Hash Generator

```json
{
  "name": "CUSTOM_IP_HASH",
  "type": "custom_algorithm",
  "description": "Hash based on IP addresses and ports",
  "configuration": {
    "algorithm": "sha256",
    "fields": ["ip_src", "ip_dst", "port_src", "port_dst"]
  }
}
```

**Dostupné algoritmy**: `md5`, `sha1`, `sha256`, `sha512`
**Dostupné polia**: Všetky sieťové polia uvedené vyššie
**Prípad použitia**: Sieťová vrstva fingerprinting

### 3. Python Script Hash Generator

```json
{
  "name": "CUSTOM_SCRIPT_HASH",
  "type": "python_script",
  "description": "Custom Python script for advanced processing",
  "script_path": "scripts/custom_generators/advanced_hash.py"
}
```

**Požiadavky**:
- Skript musí implementovať funkciu `generate_hash(packet, sni, **kwargs)`
- Musí vrátiť hash string alebo None
- Cesta k skriptu je relatívna k root projektu

**Prípad použitia**: Pokročilé vlastné spracovanie

## Custom hash typy - Príklady použitia

### Vytvorenie Custom Hash Types v GUI

1. **Prihlásenie** do systému
2. **Navigácia** na "Custom Hash Types"
3. **Kliknutie** "Create New"
4. **Vyplnenie** formulára:
   - **Name**: Jedinečný identifikátor (napr. "CUSTOM_TLS_SIMPLE")
   - **Display Name**: Ľudsky čitateľný názov
   - **Type**: simple_tls, custom_algorithm, alebo python_script
   - **Configuration**: Výber polí a algoritmu
   - **Public**: Urobiť dostupné pre ostatných používateľov
5. **Uloženie** konfigurácie

### Testovanie Custom Hash Types

1. **Navigácia** na zoznam custom hash types
2. **Kliknutie** "Test" na požadovaný hash type
3. **Upload** APK súborov alebo zadanie package names
4. **Spustenie** testu
5. **Zobrazenie** výsledkov s custom hashes

### Použitie v Hash Generátore

#### Databázový režim
```bash
# Automatické načítanie z databázy
python3 hash_generator.py input.pcap '["CUSTOM_TLS_SIMPLE"]'
```

#### Manuálny režim
```bash
# Použitie manuálnej JSON konfigurácie
export USE_MANUAL_CONFIG=true
python3 hash_generator.py input.pcap '["CUSTOM_TLS_SIMPLE"]'
```

### Príklad výstupu

```json
[
  {
    "ja3_hash": "f79b6bad2ad0641e1921aef10262856b",
    "ja3s_hash": "eb1d94daa7e0344597e756a1fb6e7054",
    "ja4_hash": "t13d1513h1_8daaf6152771_eca864cca44a",
    "ja4s_hash": "t130200_1301_234ea6891581",
    "ja4x_hash": [],
    "sni": "gateway.discord.gg",
    "ip_src": "192.168.1.100",
    "port_src": 12345,
    "ip_dest": "104.16.132.229",
    "port_dest": 443,
    "custom_CUSTOM_TLS_SIMPLE": "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6",
    "custom_CUSTOM_IP_HASH": "9f8e7d6c5b4a3928170654f3e2d1c0b9a",
    "custom_CUSTOM_TLS_ADVANCED": "c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9"
  }
]
```

## Custom hash typy - API referencie

### Laravel API Endpointy

#### Získanie Custom Hash Types pre Python Generator
```http
POST /api/custom-hash-types/python-generator
Content-Type: application/json
Authorization: Bearer python_hash_generator_key_1234567890

{
  "names": ["CUSTOM_TLS_SIMPLE", "CUSTOM_IP_HASH"]
}
```

**Odpoveď:**
```json
{
  "generators": [
    {
      "name": "CUSTOM_TLS_SIMPLE",
      "display_name": "Custom TLS Simple",
      "description": "Simple TLS hash based on version and cipher suite",
      "type": "simple_tls",
      "configuration": {
        "fields": ["version", "ciphers"]
      }
    }
  ],
  "count": 1
}
```

### Python API

#### Načítanie Custom Hash Types
```python
from dynamic_hash_loader import load_custom_hash_types_from_database

# Databázový režim
generators = load_custom_hash_types_from_database(["CUSTOM_TLS_SIMPLE"])

# Manuálny režim
generators = load_custom_hash_types_from_database(
    ["CUSTOM_TLS_SIMPLE"], 
    use_manual_config=True
)
```

#### Vytvorenie Custom Generator
```python
from custom_hash_generators import SimpleTLSHashGenerator

generator = SimpleTLSHashGenerator(
    name="CUSTOM_TLS_SIMPLE",
    description="Simple TLS hash",
    fields=["version", "ciphers"]
)
```

## Custom hash typy - Testovanie (Aktualizované 2024-12-27)

### Kompletný test systém
```bash
# Hlavný test súbor: scripts/tests/test_custom_hash_system.py
cd /home/xbwolf02/www/sem_project/scripts/tests
python3 test_custom_hash_system.py --test all

# Test základných generátorov
python3 test_custom_hash_system.py --test basic

# Test databázového pripojenia
python3 test_custom_hash_system.py --test database

# Test manuálnej konfigurácie
python3 test_custom_hash_system.py --test manual

# Test s PCAP súborom
python3 test_custom_hash_system.py --test all --pcap /path/to/file.pcap
```

### Testovanie custom hash generovania
```bash
# Test s databázovými custom hash types
cd /home/xbwolf02/www/sem_project/scripts
LARAVEL_BASE_URL=http://localhost:8000 LARAVEL_API_KEY=python_hash_generator_key_test \
python3.11 hash_generator.py /path/to/file.pcap '["CUSTOM_TLS_01"]'

# Test s viacerými custom hash types
LARAVEL_BASE_URL=http://localhost:8000 LARAVEL_API_KEY=python_hash_generator_key_test \
python3.11 hash_generator.py /path/to/file.pcap '["CUSTOM_TLS_01", "CUSTOM_IP_HASH"]'

# Test s manuálnou konfiguráciou
export USE_MANUAL_CONFIG=true
python3.11 hash_generator.py /path/to/file.pcap '["CUSTOM_TLS_SIMPLE"]'
```

### Testovanie performance a caching
```bash
# Test caching mechanizmu (malo by sa načítať len raz)
LARAVEL_BASE_URL=http://localhost:8000 LARAVEL_API_KEY=python_hash_generator_key_test \
python3.11 hash_generator.py /path/to/file.pcap '["CUSTOM_TLS_01"]' | grep "Loading custom generators"

# Výstup by mal byť:
# Loading custom generators for: ['CUSTOM_TLS_01']
```

### Testovanie fallback logiky
```python
# Test s packety bez TLS vrstvy
from scapy.all import *
from scripts.hash_generator import generate_custom_hashes

packet = IP(src="192.168.1.1", dst="192.168.1.2") / TCP(sport=80, dport=443)
custom_hashes = generate_custom_hashes(packet, None, ['CUSTOM_TLS_01'])
print(f"Fallback hashe: {custom_hashes}")
# Výstup by mal obsahovať custom hash aj pre non-TLS packet
```
```

## Custom hash typy - Riešenie problémov

### Bežné problémy

#### 1. API pripojenie zlyhalo
**Problém**: Python skript sa nemôže pripojiť k Laravel API

**Riešenia**:
- Skontrolujte `LARAVEL_BASE_URL` environment variable
- Overte, že Laravel server beží
- Skontrolujte API key v `LARAVEL_API_KEY`

#### 2. Custom Hash Type sa nenašiel
**Problém**: Custom hash type sa nenašiel v databáze

**Riešenia**:
- Skontrolujte, či je custom hash type aktívny (`is_active = true`)
- Overte, že názov je správny
- Skontrolujte oprávnenia používateľa

#### 3. Python skript chyba
**Problém**: Python skript zlyhá počas generovania hash

**Riešenia**:
- Skontrolujte syntax Python skriptu
- Overte, že skript implementuje funkciu `generate_hash`
- Skontrolujte Laravel logy

#### 4. Neplatná JSON konfigurácia
**Problém**: Manuálny konfiguračný súbor má neplatný JSON

**Riešenia**:
- Validujte JSON syntax
- Skontrolujte, či názvy polí sú podporované
- Overte štruktúru konfigurácie

### Debug režim

Povoľte debug režim pre podrobné informácie:

```python
import logging
logging.basicConfig(level=logging.DEBUG)

# Načítanie generátorov s debug informáciami
generators = load_custom_hash_types_from_database(use_manual_config=True)
```

## Custom hash typy - Bezpečnosť

### API Key autentifikácia
- Python skripty používajú API key pre autentifikáciu
- API key sa generuje dynamicky pre každé testovanie
- Middleware `AuthPythonScript` validuje API key

### Bezpečnosť uploadu súborov
- Python súbory sa validujú pred uložením
- Cesty k súborom sa kontrolujú pre bezpečnosť
- Upload je obmedzený na .py súbory

### Prístup k databáze
- Python skripty majú prístup len na čítanie
- Nemôžu modifikovať databázu
- Používajú špeciálny API endpoint

### Bezpečnosť konfigurácie
- JSON konfiguračné súbory sa validujú
- Názvy polí sú obmedzené na podporované hodnoty
- Cesty k skriptom sa validujú

## Custom hash typy - Performance (Aktualizované 2024-12-27)

### Global Caching System
```python
# Global cache for custom hash generators
_custom_generators_cache = {}

def load_custom_generators_once(custom_generators, use_manual_config):
    """
    Load custom generators only once and cache them for performance optimization.
    Eliminates rate limiting by loading generators only once per session.
    """
    cache_key = f"{use_manual_config}_{','.join(sorted(custom_generators))}"
    
    if cache_key not in _custom_generators_cache:
        print(f"Loading custom generators for: {custom_generators}")
        _custom_generators_cache[cache_key] = load_custom_hash_types_from_database(custom_generators, use_manual_config)
    
    return _custom_generators_cache[cache_key]
```

### Optimized Hash Generation
- **Single API call**: Generátory sa načítavajú len raz na začiatku
- **Memory efficient**: Caching zabraňuje opakovanému načítavaniu
- **Rate limiting protection**: Eliminuje 429 Too Many Requests chyby
- **Non-TLS processing**: Custom hashe sa generujú aj pre packety bez TLS vrstvy

### Enhanced Processing
```python
# Process custom hashes for non-TLS packets (or packets without decoded TLS)
elif packet.haslayer(TCP) and custom_generators:
    # Generate custom hashes for non-TLS packets
    custom_hashes = generate_custom_hashes(packet, None, custom_generators)
    
    # Insert or update custom hashes for non-TLS packets
    if key not in results:
        result_entry = {
            "ja3_hash": None,
            "sni": None,
            "ip_src": ip_src,
            "port_src": port_src,
            "ip_dest": ip_dest,
            "port_dest": port_dest,
            "ja3s_hash": None,
            "ja4_hash": None,
            "ja4s_hash": None,
            "ja4x_hash": []
        }
        # Add custom hashes
        for custom_name, custom_value in custom_hashes.items():
            clean_name = custom_name.replace('custom_', '') if custom_name.startswith('custom_') else custom_name
            result_entry[f"custom_{clean_name}"] = custom_value
        results[key] = result_entry
```

### Fallback systém
- Ak API zlyhá, používa sa fallback na JSON config
- Manuálny režim pre development
- Graceful degradation
- TLS generátory môžu spracovať aj packety bez TLS vrstvy pomocou fallback polí

### Environment variables
- `LARAVEL_BASE_URL` - URL Laravel servera
- `LARAVEL_API_KEY` - API key pre autentifikáciu
- `USE_MANUAL_CONFIG` - Povoliť manuálny konfiguračný režim

## Custom hash typy - Najlepšie praktiky

### Správa konfigurácie
1. **Verziové riadenie**: Udržujte konfiguračné súbory v verziovom riadení
2. **Dokumentácia**: Jasne dokumentujte vlastné generátory
3. **Testovanie**: Testujte konfigurácie pred nasadením
4. **Zálohovanie**: Udržujte zálohy funkčných konfigurácií
5. **Validácia**: Validujte JSON syntax pred použitím

### Vývojový workflow
1. **Začnite s manuálnou konfiguráciou**: Použite manuálny režim pre development
2. **Testujte dôkladne**: Testujte všetky konfigurácie pred databázovým nasadením
3. **Použite opisné názvy**: Vyberte jasné, opisné názvy pre generátory
4. **Dokumentujte polia**: Dokumentujte, ktoré polia sa používajú a prečo
5. **Monitorujte performance**: Sledujte metriky použitia a performance

### Produkčné nasadenie
1. **Použite databázový režim**: Použite databázovú integráciu pre produkciu
2. **Bezpečné API kľúče**: Použite bezpečnú generáciu API kľúčov
3. **Monitorujte použitie**: Sledujte štatistiky použitia
4. **Pravidelné zálohy**: Zálohujte databázové konfigurácie
5. **Spracovanie chýb**: Implementujte správne spracovanie chýb

### Pokyny pre výber polí
1. **TLS polia**: Použite pre TLS-špecifický fingerprinting
2. **Sieťové polia**: Použite pre analýzu sieťovej vrstvy
3. **Vlastné polia**: Použite pre pokročilé spracovanie
4. **Performance**: Zvážte performance extrakcie polí
5. **Unikátnosť**: Vyberte polia, ktoré poskytujú dobrú unikátnosť

## Python implementácia

### Hlavný hash generátor
```python
# scripts/hash_generator.py
def create_JA3_hash(packet):
    """Generuje JA3 hash z TLS Client Hello packetu"""
    if not packet.haslayer(TLS) or not packet.haslayer(TLSClientHello):
        return None
    
    tls_layer = packet[TLS]
    client_hello = tls_layer[TLSClientHello]
    
    # Extrakcia TLS verzie
    version = client_hello.version
    
    # Extrakcia cipher suít
    ciphers = client_hello.ciphers
    cipher_string = ",".join(map(str, ciphers)) if ciphers else ""
    
    # Extrakcia extensions
    extensions = client_hello.ext
    extension_string = ",".join(map(str, [ext.type for ext in extensions])) if extensions else ""
    
    # Kombinácia a hash
    ja3_string = f"{version},{cipher_string},{extension_string}"
    return hashlib.md5(ja3_string.encode()).hexdigest()
```

### Custom hash integrácia
```python
def generate_custom_hashes(packet, sni=None, custom_generators=None):
    """Generuje custom hashes pomocou vlastných generátorov"""
    if not custom_generators:
        return {}
    
    try:
        use_manual_config = os.getenv('USE_MANUAL_CONFIG', 'false').lower() == 'true'
        db_generators = load_custom_hash_types_from_database(custom_generators, use_manual_config)
        
        if db_generators:
            results = {}
            for name, generator in db_generators.items():
                if generator.validate_packet(packet):
                    hash_value = generator.generate_hash(packet, sni)
                    results[f'custom_{name}'] = hash_value
            return results
    except Exception as e:
        print(f"Error generating custom hashes: {e}")
    
    return {}
```

## Laravel backend

### Model pre Custom Hash Types
```php
// app/Models/CustomHashType.php
class CustomHashType extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id', 'name', 'display_name', 'description', 'type',
        'configuration', 'script_path', 'is_active', 'is_public', 'usage_count',
    ];
    
    protected $casts = [
        'configuration' => 'array',
        'is_active' => 'boolean',
        'is_public' => 'boolean',
        'usage_count' => 'integer',
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function hashes(): HasMany
    {
        return $this->hasMany(Hash::class, 'custom_hash_type_id');
    }
    
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }
    
    public function incrementUsage()
    {
        $this->increment('usage_count');
    }
}
```

### Controller pre Custom Hash Types
```php
// app/Http/Controllers/CustomHashTypeController.php
class CustomHashTypeController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        $customHashTypes = CustomHashType::where(function($query) use ($user) {
            $query->where('user_id', $user->id)
                  ->orWhere('is_public', true);
        })
        ->active()
        ->with('user')
        ->paginate(10);
        
        return view('custom-hash-types.index', compact('customHashTypes'));
    }
    
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:custom_hash_types,name',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:simple_tls,custom_algorithm,python_script',
            'configuration' => 'required|array',
            'script_file' => 'nullable|file|mimes:py|max:1024',
            'is_public' => 'boolean',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        $data = $validator->validated();
        $data['user_id'] = Auth::id();
        
        // Spracovanie Python skriptu
        if ($request->hasFile('script_file') && $data['type'] === 'python_script') {
            $scriptFile = $request->file('script_file');
            $fileName = Str::slug($data['name']) . '_' . time() . '.py';
            $scriptPath = 'scripts/custom_generators/' . $fileName;
            Storage::disk('local')->put($scriptPath, file_get_contents($scriptFile->getRealPath()));
            $data['script_path'] = $scriptPath;
        }
        
        // Validácia konfigurácie
        $this->validateConfiguration($data['type'], $data['configuration']);
        
        $customHashType = CustomHashType::create($data);
        
        return redirect()->route('custom-hash-types.show', $customHashType)
            ->with('success', 'Custom hash type created successfully.');
    }
}
```

## API rozhranie

### REST API endpointy
```php
// routes/api.php
Route::group(['middleware' => ['auth:sanctum']], function () {
    // Custom Hash Types API routes
    Route::get('/custom-hash-types', [CustomHashTypeController::class, 'apiIndex']);
    Route::post('/custom-hash-types', [CustomHashTypeController::class, 'store']);
    Route::get('/custom-hash-types/{customHashType}', [CustomHashTypeController::class, 'show']);
    Route::put('/custom-hash-types/{customHashType}', [CustomHashTypeController::class, 'update']);
    Route::delete('/custom-hash-types/{customHashType}', [CustomHashTypeController::class, 'destroy']);
    Route::post('/custom-hash-types/{customHashType}/test', [CustomHashTypeController::class, 'test']);
    Route::post('/custom-hash-types/python-generator', [CustomHashTypeController::class, 'getForPythonGenerator']);
});

// Python script routes (no authentication required, but API key validation)
Route::group(['middleware' => ['python']], function () {
    Route::post('/custom-hash-types/python-generator', [CustomHashTypeController::class, 'getForPythonGenerator']);
});
```

### API odpovede
```json
{
  "generators": [
    {
      "name": "CUSTOM_TLS_SIMPLE",
      "display_name": "Custom TLS Simple",
      "description": "Simple TLS hash based on version and cipher suite",
      "type": "simple_tls",
      "configuration": {
        "fields": ["version", "ciphers"]
      }
    }
  ],
  "count": 1
}
```

## Admin panel

### Admin rozhranie pre Custom Hash Types
```jsx
// resources/js/components/web/admin/CustomHashTypes.jsx
const CustomHashTypes = () => {
    const [customHashTypes, setCustomHashTypes] = useState([]);
    const [loading, setLoading] = useState(true);
    
    useEffect(() => {
        fetchCustomHashTypes();
    }, []);
    
    const fetchCustomHashTypes = async () => {
        try {
            const response = await axios.get('/api/custom-hash-types');
            setCustomHashTypes(response.data);
        } catch (error) {
            console.error('Error fetching custom hash types:', error);
        } finally {
            setLoading(false);
        }
    };
    
    return (
        <div className="container">
            <h2>Custom Hash Types Management</h2>
            <Button variant="primary" onClick={() => setShowCreateModal(true)}>
                Create New Custom Hash Type
            </Button>
            
            <Table striped bordered hover>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Display Name</th>
                        <th>Type</th>
                        <th>Usage Count</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {customHashTypes.map(hashType => (
                        <tr key={hashType.id}>
                            <td>{hashType.name}</td>
                            <td>{hashType.display_name}</td>
                            <td>{hashType.type}</td>
                            <td>{hashType.usage_count}</td>
                            <td>
                                <Button variant="info" size="sm" onClick={() => testHashType(hashType.id)}>
                                    Test
                                </Button>
                                <Button variant="danger" size="sm" onClick={() => deleteHashType(hashType.id)}>
                                    Delete
                                </Button>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </Table>
        </div>
    );
};
```

## Frontend implementácia

### React komponenty pre Custom Hash Types
```jsx
// resources/js/components/web/user/partials/CreateCustomHashType.jsx
const CreateCustomHashType = ({ show, onHide, onSuccess }) => {
    const [formData, setFormData] = useState({
        name: '',
        display_name: '',
        description: '',
        type: 'simple_tls',
        configuration: {},
        is_public: false
    });
    
    const [configurationFields, setConfigurationFields] = useState([]);
    
    const updateConfigurationFields = () => {
        switch (formData.type) {
            case 'simple_tls':
                setConfigurationFields([
                    { key: 'fields', type: 'array', label: 'TLS Fields', 
                      options: ['version', 'ciphers', 'extensions', 'sni', 'timestamp'] }
                ]);
                break;
            case 'custom_algorithm':
                setConfigurationFields([
                    { key: 'algorithm', type: 'select', label: 'Hash Algorithm', 
                      options: ['md5', 'sha1', 'sha256', 'sha512'] },
                    { key: 'fields', type: 'array', label: 'Fields', 
                      options: ['ip_src', 'ip_dst', 'port_src', 'port_dest', 'timestamp', 'packet_size', 'sni'] }
                ]);
                break;
            case 'python_script':
                setConfigurationFields([]);
                break;
            default:
                setConfigurationFields([]);
        }
    };
    
    useEffect(() => {
        updateConfigurationFields();
    }, [formData.type]);
    
    const handleSubmit = async (e) => {
        e.preventDefault();
        
        try {
            const formDataToSend = new FormData();
            Object.keys(formData).forEach(key => {
                if (key === 'configuration') {
                    formDataToSend.append(key, JSON.stringify(formData[key]));
                } else {
                    formDataToSend.append(key, formData[key]);
                }
            });
            
            if (formData.type === 'python_script' && scriptFile) {
                formDataToSend.append('script_file', scriptFile);
            }
            
            const response = await axios.post('/api/custom-hash-types', formDataToSend, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
            
            onSuccess(response.data);
            onHide();
        } catch (error) {
            console.error('Error creating custom hash type:', error);
        }
    };
    
    return (
        <Modal show={show} onHide={onHide} size="lg">
            <Modal.Header closeButton>
                <Modal.Title>Create Custom Hash Type</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <Form onSubmit={handleSubmit}>
                    <Form.Group className="mb-3">
                        <Form.Label>Name</Form.Label>
                        <Form.Control
                            type="text"
                            value={formData.name}
                            onChange={(e) => setFormData({...formData, name: e.target.value})}
                            placeholder="CUSTOM_TLS_SIMPLE"
                            required
                        />
                    </Form.Group>
                    
                    <Form.Group className="mb-3">
                        <Form.Label>Display Name</Form.Label>
                        <Form.Control
                            type="text"
                            value={formData.display_name}
                            onChange={(e) => setFormData({...formData, display_name: e.target.value})}
                            placeholder="Custom TLS Simple"
                            required
                        />
                    </Form.Group>
                    
                    <Form.Group className="mb-3">
                        <Form.Label>Type</Form.Label>
                        <Form.Select
                            value={formData.type}
                            onChange={(e) => setFormData({...formData, type: e.target.value})}
                        >
                            <option value="simple_tls">Simple TLS</option>
                            <option value="custom_algorithm">Custom Algorithm</option>
                            <option value="python_script">Python Script</option>
                        </Form.Select>
                    </Form.Group>
                    
                    {configurationFields.map(field => (
                        <Form.Group key={field.key} className="mb-3">
                            <Form.Label>{field.label}</Form.Label>
                            {field.type === 'select' ? (
                                <Form.Select
                                    value={formData.configuration[field.key] || ''}
                                    onChange={(e) => setFormData({
                                        ...formData,
                                        configuration: {
                                            ...formData.configuration,
                                            [field.key]: e.target.value
                                        }
                                    })}
                                >
                                    {field.options.map(option => (
                                        <option key={option} value={option}>{option}</option>
                                    ))}
                                </Form.Select>
                            ) : field.type === 'array' ? (
                                <div>
                                    {field.options.map(option => (
                                        <Form.Check
                                            key={option}
                                            type="checkbox"
                                            label={option}
                                            checked={formData.configuration[field.key]?.includes(option) || false}
                                            onChange={(e) => {
                                                const currentFields = formData.configuration[field.key] || [];
                                                const newFields = e.target.checked
                                                    ? [...currentFields, option]
                                                    : currentFields.filter(f => f !== option);
                                                setFormData({
                                                    ...formData,
                                                    configuration: {
                                                        ...formData.configuration,
                                                        [field.key]: newFields
                                                    }
                                                });
                                            }}
                                        />
                                    ))}
                                </div>
                            ) : null}
                        </Form.Group>
                    ))}
                    
                    <Form.Group className="mb-3">
                        <Form.Check
                            type="checkbox"
                            label="Public (available to other users)"
                            checked={formData.is_public}
                            onChange={(e) => setFormData({...formData, is_public: e.target.checked})}
                        />
                    </Form.Group>
                </Form>
            </Modal.Body>
            <Modal.Footer>
                <Button variant="secondary" onClick={onHide}>Cancel</Button>
                <Button variant="primary" onClick={handleSubmit}>Create</Button>
            </Modal.Footer>
        </Modal>
    );
};
```

## Používanie systému

### Základné použitie
1. **Spustenie hash generátora**:
   ```bash
   python3 hash_generator.py input.pcap
   ```

2. **Použitie s custom hash typmi**:
   ```bash
   python3 hash_generator.py input.pcap '["CUSTOM_TLS_SIMPLE"]'
   ```

3. **Manuálny režim**:
   ```bash
   export USE_MANUAL_CONFIG=true
   python3 hash_generator.py input.pcap '["CUSTOM_TLS_SIMPLE"]'
   ```

### Vytvorenie vlastného hash typu
1. **GUI rozhranie**: Použite web rozhranie pre vytvorenie
2. **Manuálna konfigurácia**: Upravte `custom_hash_config.json`
3. **Python skript**: Vytvorte vlastný Python skript

### Testovanie
1. **Test API integrácie**: `python3 test_api_integration.py`
2. **Test manuálnej konfigurácie**: `python3 test_config_only.py`
3. **Test hash generátora**: Spustite s testovacími súbormi

Táto dokumentácia pokrýva kompletný systém hash generovania v SEM Project, vrátane základných hash typov, custom hash systému, API rozhrania, admin panelu a frontend implementácie. Všetky aspekty systému sú podrobne zdokumentované s príkladmi kódu a pokynmi na použitie.

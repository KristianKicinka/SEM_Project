# Admin Panel - Custom Hash Types

## Prehľad

Admin panel pre Custom Hash Types umožňuje registrovným používateľom vytvárať, spravovať a testovať vlastné typy odtlačkov mobilných aplikácií. Systém je dostupný pre všetkých autentifikovaných používateľov (admin aj basic_user).

## Funkcionalita

### 1. Vytváranie Custom Hash Types

Používatelia môžu vytvárať tri typy custom hash generátorov:

#### Simple TLS Hash
- Založený na konfigurovateľných TLS poliach
- Dostupné polia: version, ciphers, extensions, sni, timestamp
- Automatické generovanie MD5 hash

#### Custom Algorithm Hash
- Používa vlastný hash algoritmus (MD5, SHA1, SHA256, SHA512)
- Konfigurovateľné polia: ip_src, ip_dst, port_src, port_dest, timestamp, packet_size, sni
- Flexibilné kombinovanie polí

#### Python Script Hash
- Externý Python skript s vlastnou logikou
- Musí implementovať funkciu `generate_hash(packet, sni, **kwargs)`
- Maximálna flexibilita pre pokročilé prípady použitia

### 2. Správa Custom Hash Types

- **Zobrazenie**: Zoznam všetkých custom hash types (vlastné + verejné)
- **Editácia**: Úprava existujúcich custom hash types
- **Mazanie**: Odstránenie custom hash types
- **Aktivácia/Deaktivácia**: Zapínanie/vypínanie custom hash types
- **Verejnosť**: Nastavenie, či môžu iní používatelia používať váš custom hash type

### 3. Testovanie Custom Hash Types

- **APK súbory**: Upload a testovanie s APK súbormi
- **Package Names**: Testovanie s názvami balíčkov aplikácií
- **Real-time výsledky**: Okamžité zobrazenie výsledkov testovania
- **Detaily**: Počet vygenerovaných hashov, stav testu, chybové hlásenia

## Použitie

### Prístup k Admin Panelu

1. Prihláste sa do systému
2. V sidebar menu kliknite na "Custom Hash Types"
3. Dostanete sa na stránku `/admin/custom-hash-types` (admin) alebo `/user/custom-hash-types` (user)

### Vytvorenie nového Custom Hash Type

1. Kliknite na tlačidlo "Create New"
2. Vyplňte formulár:
   - **Name**: Jedinečný identifikátor (napr. `CUSTOM_TLS_SIMPLE`)
   - **Display Name**: Zobrazovaný názov (napr. `Custom TLS Simple`)
   - **Description**: Popis funkcionality
   - **Type**: Vyberte typ generátora
   - **Configuration**: Nakonfigurujte parametre podľa typu
   - **Script File**: Pre Python Script typ nahrajte .py súbor
   - **Make Public**: Označte, ak chcete umožniť iným používateľom používať
3. Kliknite "Create"

### Testovanie Custom Hash Type

1. V zozname custom hash types kliknite na tlačidlo "Play" (▶️)
2. Vyberte spôsob testovania:
   - **APK Files**: Nahrajte APK súbory
   - **Package Names**: Zadajte názvy balíčkov (jeden na riadok)
3. Kliknite "Run Test"
4. Sledujte výsledky v real-time

## API Integrácia

### Dostupné API Endpointy

#### Pre autentifikovaných používateľov:
- `GET /api/custom-hash-types` - Zoznam dostupných custom hash types
- `POST /api/custom-hash-types` - Vytvorenie nového custom hash type
- `GET /api/custom-hash-types/{id}` - Detail custom hash type
- `PUT /api/custom-hash-types/{id}` - Aktualizácia custom hash type
- `DELETE /api/custom-hash-types/{id}` - Zmazanie custom hash type
- `POST /api/custom-hash-types/{id}/test` - Testovanie custom hash type

#### Pre externé API:
- `POST /api/get-custom-hash-types` - Získanie dostupných custom hash types pre API používateľa

### Použitie v API volaniach

#### Vytvorenie hashov s custom hash types:

```bash
# APK súbor s custom hash types
curl -X POST "https://your-domain.com/api/create-hash-from-apk" \
  -H "Content-Type: multipart/form-data" \
  -F "auth_key=your_api_key" \
  -F "apk_file=@app.apk" \
  -F "custom_hash_types[]=1" \
  -F "custom_hash_types[]=2"

# Package name s custom hash types
curl -X POST "https://your-domain.com/api/create-hash-from-package-name" \
  -H "Content-Type: application/json" \
  -d '{
    "auth_key": "your_api_key",
    "package_name": "com.example.app",
    "custom_hash_types": [1, 2]
  }'
```

#### Získanie dostupných custom hash types:

```bash
curl -X POST "https://your-domain.com/api/get-custom-hash-types" \
  -H "Content-Type: application/json" \
  -d '{
    "auth_key": "your_api_key"
  }'
```

## Technické detaily

### Databázová štruktúra

#### custom_hash_types tabuľka:
- `id` - Primárny kľúč
- `user_id` - ID používateľa, ktorý vytvoril custom hash type
- `name` - Jedinečný názov (napr. CUSTOM_TLS_SIMPLE)
- `display_name` - Zobrazovaný názov
- `description` - Popis
- `type` - Typ generátora (simple_tls, custom_algorithm, python_script)
- `configuration` - JSON konfigurácia
- `script_path` - Cesta k Python skriptu (ak je type=python_script)
- `is_active` - Aktívny stav
- `is_public` - Verejný prístup
- `usage_count` - Počet použití
- `created_at`, `updated_at` - Časové značky

#### hashes tabuľka (rozšírená):
- `custom_hash_type_id` - Referencia na custom hash type
- `custom_hashes` - JSON s custom hash hodnotami

### Bezpečnosť

- **Autorizácia**: Používatelia môžu upravovať len svoje vlastné custom hash types
- **Verejné custom hash types**: Môžu používať všetci používatelia
- **Validácia**: Všetky vstupy sú validované
- **File upload**: Python skripty sú kontrolované a uložené bezpečne
- **API kľúče**: Všetky API volania vyžadujú platný API kľúč

### Performance

- **Caching**: Custom hash types sú cachované pre rýchlejší prístup
- **Queue processing**: Testovanie prebieha asynchrónne
- **File cleanup**: Dočasné súbory sa automaticky mazajú
- **Usage tracking**: Sledovanie použitia pre optimalizáciu

## Troubleshooting

### Časté problémy

1. **Custom hash type sa nevytvorí**
   - Skontrolujte, či je názov jedinečný
   - Overte konfiguráciu podľa typu generátora
   - Pre Python script typ skontrolujte, či súbor existuje

2. **Testovanie zlyháva**
   - Skontrolujte, či je custom hash type aktívny
   - Overte, či Python skript obsahuje správnu funkciu
   - Skontrolujte logy pre chybové hlásenia

3. **API volania nefungujú**
   - Overte platnosť API kľúča
   - Skontrolujte, či custom hash types existujú a sú aktívne
   - Overte formát požiadavky

### Debugging

- **Logy**: Skontrolujte Laravel logy v `storage/logs/`
- **Python chyby**: Skontrolujte výstup hash_generator.py
- **Databáza**: Overte stav custom_hash_types tabuľky

## Rozšírenie

### Pridanie nového typu generátora

1. Rozšírte `CustomHashGenerator` triedu
2. Aktualizujte `CustomHashManager._create_generator_from_config()`
3. Pridajte podporu vo frontend komponente
4. Aktualizujte dokumentáciu

### Pridanie nových polí

1. Rozšírte `_extract_field_value()` metódy
2. Aktualizujte frontend konfiguráciu
3. Pridajte validáciu
4. Aktualizujte dokumentáciu

## Podpora

Pre otázky a podporu kontaktujte vývojový tím alebo vytvorte issue v repozitári projektu.


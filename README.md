<img src="./FIT_barevne_RGB_CZ.png" width="50%">


# Platforma pro automatizované vytváření otisků mobilních aplikací

Fakulta informačních technologií Vysokého učení technického v Brně\
Bakalárska práca\
Autor: Kristián kičinka (xkicin02@vutbr.cz)\
Vedúci práce: doc. Ing. Petr Matoušek Ph.D., M.A.

## Popis projektu
Cieľom práce bolo vyvinúť platformu, ktorá by umožnila automatizované vytváranie odtlačkov TLS mobilných aplikácií pre platformu Android. Webová platforma podporuje viaceré typy odtlačkov TLS, medzi ktoré patria odtlačky: _JA3_, _JA3S_, _JA4_, _JA4S_, _JA4X_. Webová platforma je rozdelená do piatich samostatných, medzi ktoré patrí:
- získavanie súborov APK
- inštalácia a spúšťanie aplikácií
- analýza sieťovej komunikácie
- vytváranie odtlačkov mobilných aplikácií
- ukladanie a prezentácia výsledkov. 

Aplikácia podporuje viaceré typy vstupov od používateľa, medzi ktoré patrí vstup prostredníctvom súborov APK, názvu mobilnej aplikácie, ako aj súboru s názvami balíčkov mobilných aplikácií. Platforma disponuje taktiež možnosťou analyzovať zadané odtlačky TLS, prípadne súbory NetFlow a poskytnúť používateľovi informácie z internej databázy.


## Demo
Vytvorená webová platforma je nasadená na adrese :
<a href="https://hashapp.netology.sk">https://hashapp.netology.sk</a>

Prihlasovacie údaje:
* Administrátor:
  * email: admin@example.com
  * password: AdminPass123
  
* Registrovaný používateľ:
  * email: user@example.com
  * password: UserPass123

## Nasadenie

Nasadenie vytvorenej webovej platformy je realizované prostredníctvom Docker kontajnerov. V prípade, že je nasadenie spúšťané na hosťovskom zariadení po prvý krát je nutné mať zbuildené a dostupné docker images. Ich vytvorenie je možné zabezpečiť spustením skriptu `build.sh` umiestneného v adresári `/virtualzation`.

Spustením skriptu `run.sh` umiestneného v adresári `/virtualzation` je zahájený proces nasadenia systému. Skript postupne vykoná načítanie priložených Docker obrazov zodpovedných za AVD zariadenia, MariaDB server, Redis server, nastavenia jazyka PHP, Python a programu Wireshark. 

V ďalšiom kroku skript spustí preklad a nastavenie hlavnej časti aplikácie a vytvorí obraz hashapp:latest. Následne je vykonané vytvorenie a spustenie potrebných Docker kontajnerov, vytvorenie štruktúry databázového systému a importovanie testovacích dát. 

Po úspešnom dokončení nasadenia bude webová platforma k dispozícii na adrese <a href="http://localhost:8081">http://localhost:8081</a>. Databázový server bude nasadený na porte `3306` a redis server na porte `6379`. Pre správne fungovanie nasadenia je nutné, aby boli dané porty v rámci hosťovského zariadenia dostupné. 

V rámci automatizácie nasadenia bol taktiež vytvorený skript `clean.sh`, ktorý dokáže zastaviť, prípadne aj zmazať docker kontajnery, ako aj obrazy a sieťové rozhrania. 

## Použitie

Používateľ má k dispozícii webové a API rozhranie. Webové rozhranie je zložené z hlavnej stránky, stránky popisujúcej rozhranie API, databázovej stránky a rohrania registrovaného používateľa a administrátora. Prostredníctvom hlavnej stránky má používateľ možnosť vytvárať odtlačky mobilných aplikácií, vyhľadávať mobilné aplikácie, ako aj zobrazovať proces generovania a výsledky. Databázová stránka slúži na prehľadávanie už vytvorených odtlačkov mobilných aplikácií. Stránka popisujúca rozhranie API obsahuje príklady volaní a odpovedí tohto rozhrania. Po prihlásení má registrovaný používateľ možnosť zobrazovať a upravovať obľubené aplikácie, zobrazovať ním vykonané žiadosti rozhania API, ako aj vytvárať nové autentfikačné kľúče. Prostredníctvom rozhrania API je možné vykonávať analýzy zadaných odltačkov alebo mobilných aplikácií, analyzovať NetFlow súbory, ako aj vytvárať nové odtlačky mobilných aplikácií.

## Využívané technológie

V rámci výboja projektu boli využívané nasledujúce technológie:
- **[Laravel](https://laravel.com)**
- **[React](https://react.dev)**
- **[Pusher](https://pusher.com)**
- **[Python 3.11.2](https://www.python.org/downloads/release/python-3112/)**
- **[Wireshark 4.2.4](https://www.wireshark.org/docs/relnotes/wireshark-4.2.4.html)**
- **[PHP 8.2](https://www.php.net/releases/8.2/en.php)**
- **[Bootstrap 5](https://getbootstrap.com)**
- **[React Bootstrap](https://react-bootstrap.netlify.app)**
- **[Scapy](https://scapy.net)**
- **[Docker](https://www.docker.com)**
- **[JWT](https://jwt.io/introduction)**
- **[Redis](https://redis.io)**
- **[MariaDB](https://mariadb.com)**

## Poďakovanie

Rád by som poďakoval svojmu školiteľovi bakalárskej práce __doc. Ing. Petrovi Matouškovi, Ph.D., M.A.__ za odbornú pomoc, usmernenia a cenné rady pri vypracovávaní mojej bakalárskej práce.
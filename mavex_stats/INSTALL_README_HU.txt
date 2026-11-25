MAVEX STATS – 0.8.1

Cél
---
Önálló e107 statisztikai plugin. Nem függ a beépített log plugintól, nem olvassa és nem módosítja annak adatait. Az adatgyűjtés telepítés után indul.

Funkciók
--------
- Saját mavex_stats_events adatbázistábla.
- Publikus oldalletöltések mérése e_header hookon keresztül.
- Időszakválasztó: ma, 7 nap, 30 nap, 90 nap, összes.
- Időszakra szűrt egyedi látogatók és oldalmegtekintések.
- Összesített oldalmegtekintés-szám.
- Napi oldalmegtekintési oszlopdiagram, napi egyedi látogatószámmal.
- Legnézettebb oldalak és külső hivatkozó domainek listája.
- Böngésző-, eszköztípus- és képernyőméret szerinti bontás.
- A saját webhely domainje nem jelenik meg külső hivatkozóként.
- IP-cím nem kerül tárolásra.
- Reszponzív, a MAVEX Stats felületére korlátozott admin megjelenés.

0.3.2 változások
----------------
- A dashboard visszaállt a kért fekete–antracit–szürke megjelenésre.
- A fehérre kényszerített panelek és táblázatok eltávolítva.
- Az időszakválasztó az e107/Bootstrap natív gombosztályait használja.
- A táblázatok a Bootstrap sötét táblázatstílusát használják.
- A böngésző-, eszköztípus- és képernyőméret-bontás a már gyűjtött User-Agent és képernyőméret mezőkből készül.
- Az adatgyűjtő e_header.php és collect.php változatlan maradt; adatbázis-módosítás nincs.

0.2.2 előzmények
----------------
- Időszakválasztó, KPI-kártyák, napi grafikon, legnézettebb oldalak és külső hivatkozók.
- A grafikon az „Összes” nézetben az utolsó 30 napot mutatja.
- A saját domain belső hivatkozóként való megjelenítése kiszűrve.
- A napi dátumléptetés naptári napok szerint történik.

0.8.1 – Publikus statisztikai oldal véglegesített általános megjelenése
------------------------------------------------------------------------
- Egységes kék színvilágú publikus oldal, új grafikus fejléccel és kártyákkal.
- Látogatók, oldalmegtekintések és regisztrált tagok KPI-kártyái.
- Napi (30 nap), havi (12 hónap) és éves (5 év) forgalmi grafikon.
- Eszközök, böngészők és operációs rendszerek összesítése.
- Hírek és látható e107 fejezetek dinamikus megjelenítése adatbázisból.
- A publikus oldal magyar és angol feliratai külön `Hungarian_front.php` és `English_front.php` fájlokba kerültek.
- Az adminisztráció saját nyelvi fájljai és részletes dashboardja változatlan maradt.
- A publikus oldal nem függ a HITS plugintól.
- A meglévő eseménytábla és adatgyűjtés nem változott; adatbázis-migráció nem szükséges.

Telepítés és frissítés
----------------------
- Készíts biztonsági másolatot a `mavex_stats` plugin könyvtárról és az adatbázisról.
- A ZIP-ben található `mavex_stats` könyvtárat másold az `e107_plugins/` mappába, a meglévő fájlok felülírásával.
- A `mavex_stats_events` adatbázistáblát és a korábbi eseményadatokat NE töröld.
- Az e107 Admin > Adatbázis részen futtasd a pluginmappák vizsgálatát, hogy az ütemezett feladat elérhető legyen.
- Az Admin > Eszközök > Ütemezett feladatok részen engedélyezd és ütemezd napi futásra az automatikus adatmegőrzési feladatot, ha használni szeretnéd.
- A MAVEX Stats > Beállítások oldalon ellenőrizd az adatgyűjtési, hozzáférési és adatmegőrzési beállításokat.
- A publikus oldal címe: `e107_plugins/mavex_stats/stats.php`. Ellenőrizd a hozzáférést a beállított felhasználói osztállyal.
- A frissítés nem igényel adatbázis-migrációt; a korábbi eseményadatok megmaradnak.

Adatvédelem
-----------
A plugin nem tárol IP-címet. A látogatói azonosító első fél cookie-val működik (180 nap). Az adatkezelési és hozzájárulási megfelelőséget az üzemeltetőnek a saját jogi/adatvédelmi követelményei szerint ellenőriznie kell.


0.3.2 megjegyzés: az admin dashboard a e107 2.4.0 Bootstrap 3 admin osztályaira támaszkodik (panel, row/col, btn, table). A saját CSS továbbra is csak a dashboard célzott megjelenítését kezeli. Az adatgyűjtés és az adatbázisséma nem változott.

0.3.2 újdonság: a napi grafikon külön oszloppal mutatja az oldalmegtekintések és az egyedi látogatók alakulását. A két adatsor külön skálázott.

0.3.2 újdonságok:
- A napi grafikon közös skálán mutatja az oldalmegtekintéseket és az egyedi látogatókat.
- Heti és havi összesítés az utolsó 180 nap adataiból.
- Az aktuális időszak összehasonlítása az előző azonos hosszúságú időszakkal (az Összes időszaknál nem jelenik meg).

0.4.2 újdonságok
----------------
- A legnézettebb oldalak táblázata egyedi látogatószámot is mutat.
- Látogatottsági csúcsidőszakok: hét napjai és óránkénti bontás, a kiválasztott időszak alapján.
- A csúcsnap és a legforgalmasabb órasáv külön összefoglalóban jelenik meg.
- Adatbázisséma és adatgyűjtő kód nem változott.
- Adatmegőrzési/törlési beállítás nem került be ebbe a kiadásba; automatikus adattörlés csak külön, egyértelműen jóváhagyott szabály alapján készülhet.

0.4.2 megjelenítési finomítás
-----------------------------
- A csúcsidőszakok egyetlen nagy panel helyett két, egymás melletti e107 panelen jelennek meg.
- A hét napjai és a legforgalmasabb (legfeljebb 8) órasáv vízszintes kék sávot is kapott.
- A már meglévő mstats-bar stílus kerül újrahasznosításra; új CSS-keretrendszer vagy külön komponensstílus nem került be.
- Az adatlekérdezések, adatgyűjtés és adatbázisszerkezet változatlan.

0.4.2 valódi oszlopdiagramok
----------------------------
- A hét napjai és a nap 24 órája külön-külön valódi, SVG-alapú oszlopdiagramon jelenik meg.
- Függőleges értéktengely, vízszintes segédvonalak, oszlopok és értékfeliratok készülnek.
- Az oszlopok fölötti adatfeliratok egérrel is ellenőrizhetők (SVG title).
- A 24 órás grafikon minden órát megjelenít, a nulla értékeket is.
- A meglévő sötét dashboard stílus marad; csak minimális méretező CSS került hozzá.
- Adatgyűjtés, adatbázisséma és e107 core változatlan.

0.5.0 – Dashboard elrendezés és grafikonfrissítés
-------------------------------------------------
- A dashboard sorrendje a jóváhagyott látványtervhez igazodik: fejléc, időszakválasztó, KPI-k, napi forgalmi grafikon, heti és óránkénti grafikonok, majd részletes táblázatok.
- A napi forgalom valódi két adatsoros SVG vonaldiagramon jelenik meg (oldalmegtekintések és egyedi látogatók).
- A heti és 24 órás SVG oszlopdiagramok megmaradnak.
- A megjelenés MAVEX sötét antracit/kék/türkiz színvilágot használ.
- A rácsos elrendezés és panelek továbbra is az e107/Bootstrap meglévő osztályaira épülnek; nincs új CSS-keretrendszer.
- Az adatgyűjtés, adatbázisséma, meglévő statisztikai lekérdezések és e107 core fájlok nem változtak.


0.6.0 – Grafikon időnavigáció
-----------------------------
- A fő forgalmi grafikon önálló Nap / Hónap / Év nézetet kapott.
- Nap nézet: óránkénti oldalmegtekintés és egyedi látogató.
- Hónap nézet: napi oldalmegtekintés és egyedi látogató.
- Év nézet: havi oldalmegtekintés és egyedi látogató.
- Előző és következő időszakra léptető gombok kerültek a grafikon fölé.
- A jövőbeli időszakra léptetés le van tiltva.
- A grafikon kisebb magasságú lett; a két adatsoros SVG-vonaldiagram megmaradt.
- Az adatgyűjtés, adatbázisséma és e107 core fájlok változatlanok.


0.6.1 – Grafikon navigáció finomítása
-------------------------------------
- A grafikon címe minden nézetben egységesen: „Látogatottság”.
- A magyarázó szöveg rövidítve: „Bontásban látható az oldalmegtekintések és az egyedi látogatók száma.”
- A Nap / Hónap / Év nézetre váltás mindig az aktuális napra, hónapra vagy évre ugrik.
- Korábbi időszak böngészésekor megjelenik az „Aktuális” gomb, amellyel egy kattintással vissza lehet térni a jelenlegi időszakra.


0.7.0 – Beállítások és adminisztráció
-------------------------------------
- Külön adminisztrációs Beállítások oldal került a pluginba.
- Adatgyűjtés globális be-/kikapcsolása.
- Adminisztrátori látogatások számlálásának kapcsolója.
- A statisztikai áttekintő elérhetősége e107 felhasználói osztály szerint szabályozható.
- A legnézettebb oldalak és külső hivatkozások listájának rekordkorlátja 5–100 között állítható.
- A részletes dashboard blokkjai külön-külön ki-/bekapcsolhatók.
- Operációs rendszer szerinti bontás a már tárolt User-Agent mezőből.
- Teljes statisztikai adattörlés megerősítéssel.
- A külső hivatkozó megnevezés mindenhol „Külső hivatkozások” lett.
- A dashboard közös megjelenítő fájlba került; az admin és a felhasználói nézet ugyanazt a dashboardot használja.
- Új felhasználói statisztikai oldal: e107_plugins/mavex_stats/stats.php. A hozzáférést a Beállítások oldalon kiválasztott felhasználói osztály szabályozza.
- Az adatbázis eseménytáblája nem változott. Az országstatisztika és CSV-export nem része ennek a kiadásnak.

Telepítés:
- Készíts biztonsági másolatot a mavex_stats plugin könyvtárról.
- Cseréld le a plugin fájljait a ZIP tartalmára.
- Az adatbázistáblát és a korábbi eseményadatokat ne töröld.
- A plugin beállításainál ellenőrizd a hozzáférési osztályt. Alapértelmezés: Adminisztrátor.
- Teszteld az adatgyűjtés kapcsolót, az adminlátogatások kapcsolóját, a megjelenítési blokkokat és a teljes adattörlést localhoston.
- A teljes adattörlés végleges művelet; előtte készíts adatbázis-mentést.

Megjegyzés:
Az eseményadatok egy közös rekordban tárolják az oldalmegtekintést, a böngészőt, a User-Agent-et, a hivatkozási domaint és a képernyőméretet. Emiatt külön kategóriák önálló törlése nem biztonságos; a 0.7.0 teljes adattörlést biztosít.


0.7.3 – Beállítások közvetlen elérése
------------------------------------
- A Beállítások oldalra mutató gomb közvetlenül az adminisztrációs áttekintő fejlécébe került.
- Így a beállítások az adminlinkek telepítéskori gyorsítótárazásától függetlenül elérhetők.
- A 0.7.0 adatgyűjtési, jogosultsági és megjelenítési funkciói változatlanok.

0.7.5 – e107 adminmenü szerkezetének javítása: külön Áttekintő és Beállítások menüpont, egyszerű szöveges hivatkozásokkal.

0.7.5: Admin beállítási oldal finomhangolása: rövidebb választómezők, javított törlési jelölőnégyzet-elrendezés, mentés gomb körüli és oldalsó alsó térköz.

0.7.6 – A statisztikák törlése megerősítő jelölőnégyzete külön belső konténert kapott; a panelen belüli igazítás és a törlőgomb előtti távolság javítva.

0.7.8 – A statisztikák törlése résznél a megerősítő szöveg és a törlőgomb közötti távolság növelve; a gomb jobbra került, kisebb képernyőn külön sorba törik.


0.7.8 – Az adminisztrációs felület magyar és angol feliratai nyelvi konstansokba kerültek. Az adatgyűjtés, adatbázisséma és nyilvános statisztikai oldal működése nem változott.


0.7.9 – Közösségi visszajelzések alapján
---------------------------------------
- A nyilvános statisztikai oldal teljes, kimásolható URL-je jelenik meg a Beállítások oldalon.
- Ismert botok és keresőrobotok User-Agent alapú szűrése az adatgyűjtőben. A szűrés nem ismerhet fel minden automatizált klienst.
- Választható automatikus adatmegőrzés: kikapcsolva, 90, 180 vagy 365 nap.
- Az automatikus törlés e107 ütemezett feladattal történik; alapértelmezésben ki van kapcsolva.
- Az e107 adminban futtasd a pluginmappák vizsgálatát, majd az Ütemezett feladatok között állítsd be a MAVEX Stats adatmegőrzési feladatát napi futásra.
- A meglévő adatbázisséma nem változott; új tábla vagy adatbázis-migráció nem szükséges.

                                    HALADÓ WEB PROGRAMOZÁS - Projekt

AI teszt

A projekt leírása:
A projekt célja egy olyan webalkalmazás fejlesztése, amely különböző területeken teszteli a
felhasználók tudását, mesterséges intelligencia (AI) segítségével generálva és értékelve a
kérdéseket. A rendszer lehetővé teszi a felhasználók számára, hogy teszteket oldjanak meg,
nyomon kövessék fejlődésüket, és fejlesszék tudásukat emberi és mesterséges intelligencia által
létrehozott feladatok segítségével. Az alkalmazás négy hozzáférési szintet különböztet meg:
vendég, regisztrált/bejelentkezett felhasználó, kérdés- és válaszkészítő, valamint
adminisztrátor.

Vendég
• megtekintheti a weboldalon elérhető információkat,
• megismerheti a kérdéskategóriák alapadatait,
• regisztrálhat a weboldalon.

Regisztrált/bejelentkezett felhasználó
• kiválaszthatja a teszt kategóriáját és nehézségi szintjét,
• megoldhatja a teszteket, és részletes statisztikát kap az eredményekről,
• megtekintheti a válaszokhoz tartozó magyarázatokat, amelyeket manuálisan vagy AI
segítségével generáltak,
• nyomon követheti a korábbi tesztjeit és fejlődését,
• megtekintheti a tíz legnépszerűbb kategóriát,
• kérheti jelszava visszaállítását, ha elfelejtette azt.

Kérdés- és válaszkészítő
• manuálisan adhat hozzá kérdéseket és válaszokat a kiválasztott kategóriákhoz,
• kérheti az AI rendszer segítségét új kérdések, válaszok vagy magyarázatok
generálásához,
• megtekintheti, módosíthatja és törölheti a meglévő kérdéseket és válaszokat.

Adminisztrátor
• létrehozhatja, szerkesztheti és törölheti a kérdéskészítők és felhasználók fiókjait,
• engedélyezheti vagy letilthatja a felhasználói hozzáféréseket,
• megtekintheti, módosíthatja és törölheti a teszteket, kérdéseket és válaszokat,
• létrehozhatja és szerkesztheti a tesztkategóriákat,
• betekintést nyerhet a rendszer naplófájljaiba (bejelentkezések, hibák, módosítások),
• hozzáfér a felhasználói statisztikákhoz és aktivitási adatokhoz (tesztek száma,
eredmények, népszerű kategóriák).

2
A felhasználó regisztrálása biztonságos módon kell, hogy történjen és kötelező aktiválási link
elküldésével e-mail útján. A link küldést jelszóváltoztatás igényléséhez is alkalmazni kell. Nem

engedélyezhetjük az azonos e-mail címmel rendelkező felhasználói fiókok regisztrálását. Az e-
mail címnek egyedinek kell lennie, és egyeznie kell a felhasználó névvel.

Az oldal adminisztrációs részét munkamenetek (PHP) használatával kell levédeni. Az összes
felhasználói jelszót a bcrypt algoritmussal kell „hash-elni“.
Hozzon létre egy adatbázist és azon belül a táblázatokat, amelyek kielégítik a projekt összes
funkcióját
Követelmények és irányelvek

• A hallgatóknak piackutatást kell végezniük, és egy adott témában meg kell vizsgálniuk
és kipróbálniuk a meglévő weboldal, alkalmazások vagy szolgáltatások
funkcionalitását. Olyan dokumentációt kell készíteni, amely rögzíti a kutatás
eredményeit (a tesztelt rendszer neve, a tesztelt rendszer webcíme, a kiválasztott
szolgáltatás által kínált funkcionalitás leírása, a hallgató által megfigyelt előnyök és
hátrányok). A kutatási eredmények és a hasonló rendszerek működésébe való betekintés
alapján HÁROM TOVÁBBI FUNKCIÓT kell meghatározni, amely a projektben
megvalósul.
• A MySQL adatbázis használatához használjon PDO kiterjesztést a PHP nyelven belül.
A PHP programkód egy részének objektumorientáltnak kell lennie.
• A projektnek többplatformosnak (Responsive) kell lennie, és hozzá kell igazítani a
számítógépekhez és a mobil eszközökhöz is.
• A projekt keretein belül kötelező a következő technikák és technológiák használata:
HTML, CSS, JavaScript, PHP és MySQL. A Bootstrap használata ajánlott. E-mailek
küldéséhez a PHPMailer osztályt és a Mailtrap-t kell használni.
• Azokon az oldalakon, ahol adatellenőrzést hajtanak végre, kötelező kliens és a szerver
oldalon érvényesítést végrehajtani.
• A projektet fel kell tölteni az iskola webszerverére. Minden team e-mailben meg fogja
kapni a hozzáférési paramétereket.
• Keretrendszer is használható a projektben. Keretrendszer használata esetén szükséges
egy rövid útmutató elkészítése a kiválasztott keretrendszer használatához.
• Minden csapatnak vagy egyénnek az egyik Git VCS rendszert (Version Control System)
és vele együtt valamely hosting megoldást (GitHub, BitBucket, GitLab stb.) kell
használnia. Adják meg a hozzáférést a tanárnak is (chole@vts.su.ac.rs). A projekten
való munka közben el kell helyezni a programkódot a Git-tárában.
• Kötelező a Composer használata.

3
• A projektet elektronikus formában kell benyújtani (programkód, .sql fájlok és
projektdokumentáció. A dokumentáció a megadott sablon szerint készül.
• A projekt megvalósítása során nagy hangsúlyt kell helyezni a bevitt adatok biztonságára
és ellenőrzésére.

• A Bootstrap 5-höz INGYENES sablonok használhatók, melyeket A PROJEKT
IGÉNYEIHEZ MÓDOSÍTANI KELL, nem használhatja pontosan ugyanazt a
sablont. Ha sablont használunk, a dokumentációban információként tüntessük fel!
A webszerverre csak ingyenes anyagok tölthetők fel! Csak INGYENES
fényképeket használhat (saját képeket vagy olyan webhelyekről letöltött képeket,
mint a www.freeimages.com és/vagy a www.unsplash.com).

Projekt bemutató

I. bemutató

2025. december 2–3.
A létrehozott adatbázis bemutatása, a projekt tervének bemutatása, információk az
esetlegesen használt további technológiákról, könyvtárakról és/vagy API-król, valamint a
kiegészítő funkciók leírása.
A bemutató legfeljebb 10 percig tart.
A csoportok bemutatási sorrendjét időben közzétesszük.
II. bemutató
2026. január 13–14.
A projekt aktuális állapotának bemutatása és az eddig megvalósított funkciók ismertetése.
A bemutató legfeljebb 10 percig tart.
A csoportok bemutatási sorrendjét időben közzétesszük.

A kész projekt beadása a vizsgaidőszakban történik, a vizsgára való előzetes jelentkezést
követően. A projekt beadásának határideje a 2025/2026-os tanév utolsó vizsgaidőszaka.

Szabadka, 2025. november 5.
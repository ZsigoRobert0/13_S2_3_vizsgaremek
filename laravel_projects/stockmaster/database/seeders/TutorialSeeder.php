<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tutorial;

class TutorialSeeder extends Seeder
{
    public function run(): void
    {
        $tutorials = [
            [
                'Title' => 'Mi az a részvény?',
                'DifficultyLevel' => 1,
                'Tags' => json_encode(['alapok', 'részvény', 'bevezető'], JSON_UNESCAPED_UNICODE),
                'Content' => <<<TEXT
A részvény egy vállalat tulajdonrészét jelenti. Amikor egy cég részvényét megveszed, valójában a vállalat egy apró szeletének tulajdonosává válsz. Ez nem csak egy szám a képernyőn, hanem egy jogi és pénzügyi tulajdonviszony leképezése.

A részvények ára azért mozog folyamatosan, mert a piac szereplői eltérően ítélik meg a vállalat jelenlegi és jövőbeli értékét. Ha a befektetők szerint a cég jó úton halad, a kereslet nőhet, ezzel együtt az árfolyam is emelkedhet. Ha a kilátások romlanak, az ár csökkenhet.

A részvénykereskedés szempontjából fontos megérteni, hogy két külön világ találkozik benne: a hosszú távú befektetés és a rövid távú spekuláció. Egy befektető évekre vesz részvényt, mert hisz a cég növekedésében. Egy trader viszont gyakran csak az ármozgást akarja kihasználni.

A StockMaster rendszerben a részvény elsősorban kereskedhető instrumentumként jelenik meg. Itt a célod az, hogy megértsd:
- mit veszel vagy adsz el,
- mi mozgatja az árat,
- és hogyan lesz egy árfolyammozgásból nyereség vagy veszteség.

A sikeres kereskedés első lépése az, hogy ne csak kódnak vagy tickernek lásd az instrumentumot, hanem valódi piaci terméknek.
TEXT
            ],
            [
                'Title' => 'Mi az a candle?',
                'DifficultyLevel' => 1,
                'Tags' => json_encode(['chart', 'candle', 'gyertya'], JSON_UNESCAPED_UNICODE),
                'Content' => <<<TEXT
A candle, vagyis gyertya a chart egyik legalapvetőbb építőeleme. Egy adott időszak ármozgását foglalja össze négy fő adattal:
- nyitóár,
- maximum ár,
- minimum ár,
- záróár.

Ha például 1 perces chartot nézel, akkor minden gyertya 1 perc történéseit mutatja meg. Ha 5 perces chartot nézel, akkor minden gyertya 5 perc adatait sűríti össze.

A gyertya teste megmutatja, hogy a nyitó és a záróár hol helyezkedik el egymáshoz képest. A kanócok pedig azt, hogy az adott időszakban meddig jutott el az ár felfelé vagy lefelé.

Miért fontos ez?
Mert a candle nem csak szám, hanem piaci viselkedés lenyomata. Megmutatja:
- mennyire volt erős a vevői vagy eladói nyomás,
- történt-e visszautasítás egy szintről,
- és hogy az időszak végén ki maradt fölényben.

A StockMaster chartja candle-adatokból épül fel. Ezért ha megérted a gyertyákat, akkor nem csak vonalakat látsz, hanem piaci döntések nyomait.
TEXT
            ],
            [
                'Title' => 'Buy és Sell alapok',
                'DifficultyLevel' => 1,
                'Tags' => json_encode(['buy', 'sell', 'trade', 'irány'], JSON_UNESCAPED_UNICODE),
                'Content' => <<<TEXT
A Buy és Sell a kereskedés két alapművelete.

Buy esetén arra számítasz, hogy az ár emelkedni fog. Ilyenkor olcsóbban szeretnél belépni, és magasabb áron kiszállni.

Sell esetén arra számítasz, hogy az ár csökkenni fog. Ez short gondolkodást jelent: magasabb szinten adsz el, és alacsonyabban szeretnéd visszavenni vagy lezárni a pozíciót.

A két irány közötti különbség nem csak technikai, hanem gondolkodásbeli is. Sokan csak az emelkedésből akarnak profitálni, pedig a piac lefelé is adhat lehetőséget.

A StockMaster rendszerben nagyon fontos, hogy tisztán lásd:
- milyen irányba nyitsz pozíciót,
- milyen áron lépsz be,
- mekkora mennyiséggel kereskedsz,
- és hogyan változik a PnL a mozgás függvényében.

A Buy és Sell gomb nem csak két akció a felületen. Ezek határozzák meg a teljes kereskedési logikádat, a kockázatodat és a várható eredményt is.
TEXT
            ],
            [
                'Title' => 'Mi az a spread?',
                'DifficultyLevel' => 1,
                'Tags' => json_encode(['spread', 'bid', 'ask', 'árazás'], JSON_UNESCAPED_UNICODE),
                'Content' => <<<TEXT
A spread a vételi és eladási ár közötti különbség. Másképp fogalmazva:
- a vevő drágábban vesz,
- az eladó olcsóbban ad el,
- a kettő közötti rés a spread.

Ez azért fontos, mert már a belépés pillanatában hátrányból indulsz. Ha Buy pozíciót nyitsz, jellemzően az ask áron lépsz be. Ha rögtön zárnád, a bid áron zárnál. A kettő közötti eltérés azonnali költséget jelent.

Sok kezdő ezt alábecsüli, pedig rövid távú kereskedésnél a spread komoly tényező lehet. Minél kisebb mozgásokra tradelsz, annál jobban számít.

A StockMasterben a spread külön is megjelenhet, ezért fontos megérteni, hogy ez nem hiba vagy vizuális extra, hanem valódi kereskedési költség.

A jó trader nem csak a chartot nézi, hanem azt is:
- mennyibe kerül a belépés,
- mekkora a spread,
- és ez hogyan befolyásolja a stratégiáját.
TEXT
            ],
            [
                'Title' => 'Pozíció nyitás alapjai',
                'DifficultyLevel' => 1,
                'Tags' => json_encode(['pozíció', 'belépés', 'mennyiség'], JSON_UNESCAPED_UNICODE),
                'Content' => <<<TEXT
A pozíció nyitás a kereskedés egyik legfontosabb pillanata, mert itt dől el:
- milyen instrumentumba lépsz,
- milyen irányba lépsz,
- mekkora mérettel lépsz,
- és milyen áron vállalsz kockázatot.

Egy jó belépés nem attól jó, hogy mindenáron pontos. Hanem attól, hogy logikus, következetes és a stratégiádhoz illeszkedik.

Pozíció nyitás előtt mindig tedd fel magadnak a kérdéseket:
- Miért pont most lépek be?
- Hol bizonyulhatok tévesnek?
- Mekkora veszteséget vállalok?
- Mi az elvárt célár vagy menedzsment terv?

A StockMaster felületén a belépés technikailag egyszerű, de a döntés mögötte nem az. A rendszer csak végrehajtja, amit te elhatározol. Ezért a pozíciónyitás mindig gondolkodási fegyelmet is igényel.
TEXT
            ],
            [
                'Title' => 'Risk management',
                'DifficultyLevel' => 2,
                'Tags' => json_encode(['risk', 'money management', 'védelem'], JSON_UNESCAPED_UNICODE),
                'Content' => <<<TEXT
A risk management a túlélés művészete a piacon. Nem az a célja, hogy minden trade nyereséges legyen, hanem az, hogy a veszteségeid kontrolláltak maradjanak.

A legtöbb trader nem azért bukik el, mert nincs jó ötlete, hanem mert túl sokat kockáztat rosszkor. Egyetlen trade sem érhet annyit, hogy veszélybe sodorja a teljes számládat.

Risk management alatt azt értjük:
- mekkora tőkét kockáztatsz trade-enként,
- hol van az a pont, ahol kilépsz, ha nincs igazad,
- és hogyan kezeled a veszteségsorozatokat.

A jó risk management eredménye:
- kisebb érzelmi nyomás,
- stabilabb döntéshozatal,
- hosszabb távon fenntartható fejlődés.

A StockMaster oktatási logikájában ez az egyik legfontosabb haladó téma, mert hiába jó a belépésed, ha rosszul menedzseled a kockázatot, hosszú távon nem maradsz játékban.
TEXT
            ],
            [
                'Title' => 'Pozícióméretezés',
                'DifficultyLevel' => 2,
                'Tags' => json_encode(['méretezés', 'pozíció', 'kockázat'], JSON_UNESCAPED_UNICODE),
                'Content' => <<<TEXT
A pozícióméretezés azt határozza meg, hogy mekkora mennyiséggel lépsz piacra. Ez közvetlenül összefügg azzal, hogy mekkora veszteség vagy nyereség érhet egy adott ármozgás esetén.

A helyes méretezés nem érzésből történik. Nem úgy döntünk, hogy "ez most biztos jó lesz, belenyomok nagyobbat". Ehelyett a számlaméret, a kockázati százalék és a stop távolsága alapján kell meghatározni.

Ha túl nagy a pozíció:
- megnő a pszichológiai nyomás,
- túl hamar jön a pánik,
- a kisebb mozgások is aránytalanul nagy hatással lesznek rád.

Ha túl kicsi:
- lehet, hogy nem veszed komolyan a trade-et,
- vagy a stratégia nem hozza a kívánt eredményt.

A jó méretezés a stratégia része, nem különálló döntés. A StockMaster célja, hogy a kereskedési gondolkodást ne csak gombnyomásként, hanem rendszerként tanuld meg.
TEXT
            ],
            [
                'Title' => 'Támasz és ellenállás',
                'DifficultyLevel' => 2,
                'Tags' => json_encode(['support', 'resistance', 'szintek'], JSON_UNESCAPED_UNICODE),
                'Content' => <<<TEXT
A támasz és ellenállás olyan árszintek, ahol a piac korábban reagált. Ezek nem mágikus vonalak, hanem olyan zónák, ahol a vevői vagy eladói érdeklődés korábban erősebben jelent meg.

Támasz:
olyan terület, ahol az eső ár gyakran megáll vagy visszapattan.

Ellenállás:
olyan terület, ahol az emelkedő ár gyakran elakad vagy visszafordul.

Ezek a szintek segítenek:
- belépési helyzetek felismerésében,
- stop elhelyezésében,
- target meghatározásában,
- és a piaci kontextus értelmezésében.

A fontos az, hogy ne pixeles pontosságot várj tőlük. A piac ritkán fordul meg hajszálpontosan ugyanott. Inkább zónákban kell gondolkodni.

A StockMaster későbbi vizuális tutorial blokkjában ezekhez már nagyon jól lehet majd chartos példákat is kötni.
TEXT
            ],
            [
                'Title' => 'Trend és range',
                'DifficultyLevel' => 2,
                'Tags' => json_encode(['trend', 'range', 'struktúra'], JSON_UNESCAPED_UNICODE),
                'Content' => <<<TEXT
A piac nem mindig ugyanúgy mozog. Néha tiszta trendet látunk, máskor oldalazó, sávos mozgást, azaz range-et.

Trend esetén:
- az ár egy domináns irányba halad,
- vannak visszahúzódások,
- de összességében magasabb csúcsok és magasabb mélypontok, vagy ennek fordítottja látszik.

Range esetén:
- az ár két fő szint között mozog,
- nincs tiszta irány,
- a kitörések gyakran visszahúzódnak.

A legnagyobb hiba az, amikor valaki trend stratégiát akar range-ben használni, vagy fordítva. Ezért a piaci környezet felismerése alapvető.

A StockMasterben a chart, a gyertyák és a több timeframe együtt segíthetnek abban, hogy ezt a különbséget megtanuld olvasni.
TEXT
            ],
            [
                'Title' => 'Hírek hatása a piacra',
                'DifficultyLevel' => 2,
                'Tags' => json_encode(['hírek', 'volatilitás', 'fundamentum'], JSON_UNESCAPED_UNICODE),
                'Content' => <<<TEXT
A piac nem csak technikai alapon mozog. A hírek, vállalati gyorsjelentések, makrogazdasági adatok és váratlan események hirtelen és nagy mozgásokat okozhatnak.

Példák:
- kamatdöntés,
- inflációs adat,
- vállalati earnings,
- vezetői nyilatkozat,
- geopolitikai esemény.

Ilyenkor a spread nőhet, a volatilitás megugorhat, és a normál piaci viselkedés rövid időre felborulhat.

Egy haladó trader nem csak azt nézi, mit csinált az ár, hanem azt is, miért mozdulhat most másképp, mint általában.

A StockMaster későbbi fejlesztéseihez ez azért fontos, mert a news modul és a chart logika együtt sokkal erősebb oktatási élményt tud majd adni.
TEXT
            ],
            [
                'Title' => 'Több timeframe együtt olvasása',
                'DifficultyLevel' => 3,
                'Tags' => json_encode(['timeframe', 'mtf', 'kontextus'], JSON_UNESCAPED_UNICODE),
                'Content' => <<<TEXT
A több timeframe-es elemzés lényege, hogy nem csak egyetlen nézetből próbálod értelmezni a piacot. A magasabb timeframe adja a kontextust, az alacsonyabb timeframe pedig a pontosabb belépési lehetőségeket.

Például:
- 1h charton nézed az általános trendet,
- 15m charton keresed a struktúrát,
- 1m vagy 5m charton finomítod a belépést.

Ez azért hatékony, mert így nem vakon reagálsz az apró mozgásokra, hanem nagyobb szerkezetbe helyezed őket.

A több timeframe-es gondolkodás segít:
- rossz irányú tradek kiszűrésében,
- jobb belépések keresésében,
- és abban, hogy ne vessz el a zajban.

A profi kereskedés egyik ismertetőjele, hogy a trader egyszerre tud több idősíkot is összekapcsolni.
TEXT
            ],
            [
                'Title' => 'Trade naplózás',
                'DifficultyLevel' => 3,
                'Tags' => json_encode(['journal', 'review', 'fejlődés'], JSON_UNESCAPED_UNICODE),
                'Content' => <<<TEXT
A trade naplózás az egyik legerősebb fejlődési eszköz. A legtöbb ember túl hamar elfelejti, mit miért csinált egy trade-ben. A napló ezt a problémát oldja meg.

Egy jó trade journal tartalmazhatja:
- a belépés okát,
- a piac kontextusát,
- a stop és target logikáját,
- az érzelmi állapotodat,
- és a trade utólagos értékelését.

A naplózás segít felismerni:
- ismétlődő hibákat,
- erős setupokat,
- gyenge szokásokat,
- és azt, hogy valóban a rendszered szerint kereskedsz-e.

A fejlődés nem abból jön, hogy sok trade-et kötsz. Hanem abból, hogy visszanézed, elemzed és javítod a döntéseidet.
TEXT
            ],
            [
                'Title' => 'Drawdown kezelés',
                'DifficultyLevel' => 3,
                'Tags' => json_encode(['drawdown', 'veszteségsorozat', 'kontroll'], JSON_UNESCAPED_UNICODE),
                'Content' => <<<TEXT
A drawdown azt jelenti, hogy a számlád csúcsról visszaesik. Ez minden trader életében előfordul, még a jóknál is.

A kérdés nem az, hogy lesz-e drawdown, hanem az, hogyan reagálsz rá.

Rossz reakciók:
- bosszútrade,
- túlméretezés,
- kontroll nélküli visszanyerési kényszer,
- stratégia értelmetlen váltogatása.

Jó reakciók:
- kockázat csökkentése,
- teljesítmény áttekintése,
- setupok újraértékelése,
- mentális stabilitás visszaépítése.

A drawdown kezelés a profi gondolkodás egyik sarokpontja. Itt dől el, hogy valaki csak lelkes próbálkozó, vagy valódi rendszerben gondolkodó trader.
TEXT
            ],
            [
                'Title' => 'Mentális oldal',
                'DifficultyLevel' => 3,
                'Tags' => json_encode(['pszichológia', 'fegyelem', 'érzelmek'], JSON_UNESCAPED_UNICODE),
                'Content' => <<<TEXT
A kereskedés nem csak technika, hanem mentális teljesítmény is. Sokszor nem a setup rossz, hanem a végrehajtás csúszik szét félelem, kapzsiság vagy türelmetlenség miatt.

A mentális oldal fő elemei:
- fegyelem,
- türelem,
- következetesség,
- érzelemkezelés,
- önkontroll.

A legtöbb rendszer papíron működik, de a trader nem tudja következetesen végigvinni. Ezért a pszichológia nem extra téma, hanem a rendszer része.

A StockMaster oktatóanyag-rendszerében ezt nem szabad röviden elintézni, mert hosszú távon ez különbözteti meg a túlélő tradert a szétesőtől.
TEXT
            ],
            [
                'Title' => 'Saját stratégiaépítés',
                'DifficultyLevel' => 3,
                'Tags' => json_encode(['stratégia', 'rendszer', 'tesztelés'], JSON_UNESCAPED_UNICODE),
                'Content' => <<<TEXT
A saját stratégia felépítése nem egyetlen ötletből áll. Ez egy folyamat, amelyben össze kell raknod:
- milyen piacot tradelsz,
- milyen timeframe-et használsz,
- milyen setupokra lépsz,
- hogyan menedzseled a kockázatot,
- és hogyan értékeled vissza az eredményeidet.

Egy működő stratégia:
- ismételhető,
- mérhető,
- dokumentálható,
- és pszichológiailag is vállalható számodra.

Nem elég, hogy valami egyszer működött. Olyan rendszert kell építeni, amit hosszabb távon is végre tudsz hajtani.

A StockMaster egyik későbbi nagy ereje pont az lehet, hogy a kereskedési felület és az oktatási réteg összekapcsolható lesz: vagyis nem csak tanulsz valamit, hanem egyből a platformon tudod értelmezni is.
TEXT
            ],
        ];

        foreach ($tutorials as $tutorial) {
            Tutorial::updateOrCreate(
                ['Title' => $tutorial['Title']],
                $tutorial
            );
        }
    }
}
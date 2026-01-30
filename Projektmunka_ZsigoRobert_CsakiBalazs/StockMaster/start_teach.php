<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bevezetés</title>

    <style>
    body{ background:#07111f; color:#fff; font-family:system-ui; margin:0; }
    .wrap{ max-width:1100px; margin:30px auto; padding:0 14px; }
    .topbar{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:12px;
    }

    .card{ margin-top:18px; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.08); border-radius:18px; padding:16px; backdrop-filter: blur(14px); }
    .grid{ display:grid; grid-template-columns: 1fr 1fr; gap:14px; }
    .row{ display:flex; gap:10px; align-items:end; flex-wrap:wrap; }
    .field{ display:flex; flex-direction:column; gap:6px; position:relative; }

    .btn{
      background:rgba(255,255,255,0.06);
      border:1px solid rgba(255,255,255,0.08);
      color:#fff;
      border-radius:12px;
      padding:10px 14px;
      cursor:pointer;
      font-weight:600;
    }
    .btn:hover{
      background:rgba(255,255,255,0.10);
      border-color:rgba(255,255,255,0.14);
    }

    input, select{
      -webkit-appearance:none; -moz-appearance:none; appearance:none;
      background: rgba(0,0,0,0.28);
      border: 1px solid rgba(255,255,255,0.10);
      color: #fff;
      padding: 10px 42px 10px 12px;
      border-radius: 12px;
      outline: none;
      min-width: 140px;
    }
    input:hover, select:hover{ border-color: rgba(255,255,255,0.16); }
    input:focus, select:focus{
      border-color: rgba(255,255,255,0.22);
      box-shadow: 0 0 0 3px rgba(255,255,255,0.06);
    }

    .selectField::after{
      content:"▾";
      position:absolute;
      right:14px;
      top:38px;
      color: rgba(255,255,255,0.65);
      pointer-events:none;
      font-size:14px;
    }

    input[type="number"]::-webkit-outer-spin-button,
    input[type="number"]::-webkit-inner-spin-button{
      -webkit-appearance:none; margin:0;
    }
    input[type="number"]{ -moz-appearance:textfield; }

    select option{ background:#0b1422; color:#fff; }

    .ok{ color:#2dd36f; }
    .bad{ color:#ff4d4d; }
    .muted{ opacity:0.7; }

    table{ width:100%; border-collapse:collapse; }
    th, td{ padding:12px 10px; border-bottom:1px solid rgba(255,255,255,0.06); text-align:left; }
    th{ opacity:0.75; font-weight:700; }

    .tag{ display:inline-block; padding:4px 10px; border-radius:999px; font-size:12px; border:1px solid rgba(255,255,255,0.10); }
    .tag.deposit{ background:rgba(45,211,111,0.12); }
    .tag.withdrawal{ background:rgba(255,77,77,0.12); }
    .tag.trade{ background:rgba(64,155,255,0.12); }

    .backBtn{
      display:inline-flex;
      align-items:center;
      gap:8px;
      padding:10px 14px;
      border-radius:14px;
      background: rgba(255,255,255,0.06);
      border: 1px solid rgba(255,255,255,0.08);
      color:#fff;
      text-decoration:none;
      font-weight:600;
      transition:.15s ease;
      white-space:nowrap;
    }
    .backBtn:hover{
    
      background: rgba(255,255,255,0.10);
      border-color: rgba(255,255,255,0.14);
    }
    .backBtn::before{ content:"←"; opacity:.75; }

    .oktato{
         
        color:#fff;
      text-decoration:none;
      font-weight:1000;
    }
    </style>
</head>
<body>
    <div class="wrap">
        
        <div class="card">
            <h1 class="oktato">Bevezetés a tőzsde világába</h1>
            <div style="display:flex; justify-content:flex-end;">
                
               <a href="teach.php" class="backBtn">Vissza az oktatóanyagokhoz</a>

            </div>

            
            
            <div style="max-height:520px; overflow:auto; margin-top:12px; padding:12px; border-radius:8px; background:rgba(255,255,255,0.02);">
                <!-- Ide kerülhet később tartalom -->
                 <p><h1>Bevezetés a tőzsdei kereskedés világába</h1>

                    Ez az oktatóanyag teljesen az alapoktól mutatja be a tőzsdei kereskedés (tradelés) és a befektetés világát. Úgy készült, hogy akkor is érthető legyen, ha korábban semmilyen tapasztalatod nincs a tőzsdével kapcsolatban. A cél az, hogy a végére tudd:

                    <p>-mit látsz egy árfolyam-diagramon,</p>

                    <p>-hogyan kell időtávokat váltani (napi, perces, órás nézet stb.),</p>

                    <p>-mit jelent a vétel és az eladás,</p>

                    <p>-mikor és miért érdemes egyik vagy másik gombot megnyomni,</p>

                    <p>-és összességében hogyan működik a tőzsde logikája.</p>

                    <h2>1. Mi az a tőzsde, nagyon leegyszerűsítve?</h2>

                    A tőzsde egy piac, ahol emberek és intézmények egymás között adnak-vesznek különböző dolgokat. Ezek lehetnek:

                    <p>-részvények (pl. egy cég kis darabjai),</p>

                    <p>-devizák (euró, dollár stb.),</p>

                    <p>-kriptovaluták (Bitcoin, Ethereum),</p>

                    <p>-nyersanyagok (arany, olaj).</p>

                    A lényeg mindig ugyanaz: valaki venni akar, valaki eladni akar. Az ár pedig attól függ, hogy melyikből van éppen több.

                    <h2>2. Mi az a kereskedés (tradelés)?</h2>
                    A tradelés azt jelenti, hogy olcsóbban veszel, drágábban adsz el.

                    <p>Példa:</p>

                    <p>-Veszel egy részvényt 1000 euróért.</p>

                    <p>-Később eladod 1200 euróért.</p>

                    <p>-A különbség (200 Euró) a nyereséged.</p>

                    Ha fordítva történik (drágábban veszel, olcsóbban adsz el), akkor veszteséged lesz.

                    <h2>3. Az árfolyam-diagram – mit látsz valójában?</h2>

                    Amikor megnyitsz egy kereskedési felületet, az első dolog, amit látsz, az árfolyam-diagram. Ez egy rajz, ami megmutatja, hogyan változott az ár az idő múlásával.

                    <h3>3.1 Az idő (vízszintes tengely)</h3>

                    <p>-Balról jobbra halad az idő</p>

                    <p>-A bal oldal a múlt</p>

                    <p>-A jobb oldal a jelen</p>

                    <h3>3.2 Az ár (függőleges tengely)</h3>

                    <p>-Felfelé: drágább ár</p>

                    <p>-Lefelé: olcsóbb ár</p>

                    <h2>4. Gyertyák – mi az a zöld és piros rúd?</h2>

                    A legtöbb diagram úgynevezett gyertyákat használ.

                    <p>-Egy gyertya négy dolgot mutat:</p>

                    <p>-hol nyitott az ár,</p>

                    <p>-hol zárt,</p>

                    <p>-mennyire ment fel,</p>

                    <p>-mennyire ment le.</p>

                    <h4>Színek jelentése:</h4>

                    <p>Zöld gyertya: az ár emelkedett (feljebb zárt, mint ahol nyitott)</p>
                    <p>Piros gyertya: az ár csökkent (lejjebb zárt, mint ahol nyitott)</p>

                    <h2>5. Idősíkok – napi, perces, órás nézet</h2>

                    Az idősík azt határozza meg, hogy egy gyertya mennyi időt mutat.

                    <h4>Gyakori idősíkok:</h4>

                    <p>-1 perc (1m) – nagyon gyors mozgások</p>

                    <p>-5 perc (5m) – rövid távú kereskedés</p>

                    <p>-1 óra (1h) – napon belüli kereskedés</p>

                    <p>-1 nap (1D) – hosszabb táv</p>

                    <p>Példa:</p>

                    <p>1 órás nézetben egy gyertya 1 óra történéseit mutatja</p>

                    <p>napi nézetben egy gyertya egy teljes napot</p>

                    Minél nagyobb az idősík, annál lassabb, de tisztább a kép.

                    <h2>6. Trend – az ár iránya</h2>

                    A trend az ár általános iránya.

                    <p>Emelkedő trend: az ár egyre magasabb</p>

                    <p>Csökkenő trend: az ár egyre alacsonyabb</p>

                    <p>Oldalazás: az ár fel-le mozog egy sávban</p>

                    Kezdőként az egyik legfontosabb szabály: ne menj szembe a trenddel.

                    <h2>7. Vétel és eladás – a két legfontosabb gomb</h2>
                    <h3>7.1 Vétel (Buy)</h3>

                    <p>A vétel gomb megnyomásakor azt mondod:</p>

                    <p>„Szerintem az ár feljebb fog menni.”</p>

                    <p>Akkor keresel pénzt, ha az ár valóban emelkedik.</p>

                    <h3>7.2 Eladás (Sell)</h3>

                    <p>Az eladás gomb megnyomásakor azt mondod:</p>

                    <p>„Szerintem az ár lejjebb fog menni.”</p>

                    <p>Itt akkor keresel pénzt, ha az ár csökken.</p>

                    <h2>8. Mikor érdemes venni?</h2>

                    Egyszerű gondolkodás kezdőknek:

                    <p>Az ár sokat esett → lehet, hogy hamarosan emelkedik</p>

                    <p>Az ár emelkedő trendben van → vevők vannak túlsúlyban</p>

                    Fontos: Soha ne csak érzésből dönts. Mindig nézd meg:

                    <p>-az idősíkot,</p>

                    <p>-a trendet,</p>

                    <p>-az előző mozgásokat.</p>

                    <h2>9. Mikor érdemes eladni?</h2>

                    <p>Ha az ár sokat emelkedett</p>

                    <p>Ha csökkenő trend indul</p>

                    <p>Ha már van nyereséged és biztosra akarsz menni</p>

                    A tőzsdén egy szabály aranyat ér: Senki nem szegényedett el attól, hogy nyereséggel zárt.

                    <h2>10. Kockázat – amit soha nem szabad figyelmen kívül hagyni</h2>

                    A tőzsde nem szerencsejáték, de van kockázata.

                    <p>Soha ne:</p>

                    <p>-tedd fel az összes pénzed,</p>

                    <p>-kereskedj kölcsönpénzből,</p>

                    <p>-akarj gyorsan meggazdagodni.</p>

                    Mindig úgy kereskedj, hogy egy veszteség ne fájjon.

                    <h2>11. Összefoglalás</h2>

                    A tőzsde lényege egyszerű:

                    <p>-ár mozgását figyeled,</p>

                    <p>-döntést hozol (vétel vagy eladás),</p>

                    <p>-kezeled a kockázatot.</p>

                    Nem kell zseninek lenni, csak türelmesnek és fegyelmezettnek.

                    <p>Ez az anyag az alapokat adja meg. A valódi tudás tapasztalattal és gyakorlással jön meg. Próbáld ki demó számlán, gyakorolj sokat, és mindig tanulj a hibáidból!</p>
                </p>
            </div>
        </div>
 
     </div>
</body>
</html>
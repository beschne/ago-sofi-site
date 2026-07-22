<?php
$title = 'Die Sonnenfinsternis am 12. August 2026 - AG Orion';
$activeNav = 'sonnenfinsternis';

require __DIR__ . '/inc/header.php';
?>
<style>
  .sofi { --sky:#3d6690; --ridge:#0d1a14; --sun:#ffcf3f; --track:#ffd36b;
          --accent:#e8a33d; max-width:60rem; }
  .sofi figure { margin:0 0 1.25rem; }
  .sofi svg { width:100%; height:auto; display:block; border-radius:6px; }
  .sofi figcaption { font-size:.85rem; opacity:.8; margin-top:.4rem; }
  .sofi table { border-collapse:collapse; width:100%; font-variant-numeric:tabular-nums; }
  .sofi th, .sofi td { padding:.45rem .7rem; text-align:right; border-bottom:1px solid rgba(128,128,128,.3); }
  .sofi th:first-child, .sofi td:first-child { text-align:left; }
  .sofi tbody tr.max td { font-weight:700; }
  .sofi .muted { opacity:.7; }
</style>

<section class="sofi">
  <h1>Partielle Sonnenfinsternis am 12. August 2026</h1>
  <p>Am Abend des 12. August 2026 hält der Himmel über Bad Homburg eine partielle
     Sonnenfinsternis für uns bereit. Sie ereignet sich am frühen Abend, tief im Westen bis
     Westnordwesten - genau dort, wo zu dieser Jahreszeit auch die Sonne untergeht. Ganz zu
     Ende sehen werden wir sie deshalb nicht: Die Sonne verschwindet hinter dem Horizont,
     bevor der Mond sie wieder vollständig freigegeben hat.</p>

  <p>Die Finsternis beginnt um 19:19 Uhr; von da an wächst die Bedeckung, während die Sonne
     weiter sinkt. Ihren Höhepunkt erreicht sie um 20:13 Uhr mit fast 90 % Bedeckung - bei nur
     noch 4,6° Sonnenhöhe. Gegen 20:49 Uhr geht die Sonne unter, noch zu gut einem Fünftel
     verdeckt; der Rest der Finsternis (rechnerisch bis 21:04 Uhr) bleibt uns unterhalb des
     Horizonts verborgen.</p>

  <p>Alle Angaben gelten für <strong>Bad Homburg vor der Höhe</strong>
     (50,227° N, 8,618° O, 200 m ü. NN), berechnet mit Skyfield (JPL DE421).</p>

  <figure>
    <svg viewBox="0 0 1000 600" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Horizont- und Azimutskizze der partiellen Sonnenfinsternis am 12.8.2026 ueber Bad Homburg" font-family="system-ui, sans-serif">
<defs>
<mask id="sofimask0"><circle cx="318.0" cy="120.6" r="18.0" fill="white"/><circle cx="352.3" cy="133.4" r="18.6" fill="black"/></mask>
<mask id="sofimask1"><circle cx="405.0" cy="172.9" r="18.0" fill="white"/><circle cx="429.6" cy="183.2" r="18.6" fill="black"/></mask>
<mask id="sofimask2"><circle cx="490.9" cy="223.9" r="18.0" fill="white"/><circle cx="505.7" cy="231.7" r="18.6" fill="black"/></mask>
<mask id="sofimask3"><circle cx="577.0" cy="274.0" r="18.0" fill="white"/><circle cx="581.8" cy="279.4" r="18.6" fill="black"/></mask>
<mask id="sofimask4"><circle cx="626.1" cy="301.9" r="18.0" fill="white"/><circle cx="625.2" cy="306.0" r="18.6" fill="black"/></mask>
<mask id="sofimask5"><circle cx="710.0" cy="347.8" r="18.0" fill="white"/><circle cx="699.2" cy="349.7" r="18.6" fill="black"/></mask>
<mask id="sofimask6"><circle cx="780.2" cy="383.0" r="18.0" fill="white"/><circle cx="761.1" cy="383.3" r="18.6" fill="black"/></mask>
<mask id="sofimask7"><circle cx="837.7" cy="407.4" r="18.0" fill="white"/><circle cx="811.7" cy="406.5" r="18.6" fill="black"/></mask>
</defs>
<rect x="85.0" y="35.0" width="890.0" height="372.4" fill="var(--sky,#3d6690)"/>
<line x1="85.0" y1="407.4" x2="975.0" y2="407.4" stroke="var(--grid,#ffffff)" stroke-opacity="0.22"/>
<text x="77.0" y="411.4" text-anchor="end" font-size="15" fill="var(--axis-dark,#33475b)">0°</text>
<line x1="85.0" y1="297.9" x2="975.0" y2="297.9" stroke="var(--grid,#ffffff)" stroke-opacity="0.22"/>
<text x="77.0" y="301.9" text-anchor="end" font-size="15" fill="var(--axis-dark,#33475b)">5°</text>
<line x1="85.0" y1="188.3" x2="975.0" y2="188.3" stroke="var(--grid,#ffffff)" stroke-opacity="0.22"/>
<text x="77.0" y="192.3" text-anchor="end" font-size="15" fill="var(--axis-dark,#33475b)">10°</text>
<line x1="85.0" y1="78.8" x2="975.0" y2="78.8" stroke="var(--grid,#ffffff)" stroke-opacity="0.22"/>
<text x="77.0" y="82.8" text-anchor="end" font-size="15" fill="var(--axis-dark,#33475b)">15°</text>
<line x1="85.0" y1="35.0" x2="85.0" y2="407.4" stroke="var(--grid,#ffffff)" stroke-opacity="0.16"/>
<text x="85.0" y="431.4" text-anchor="middle" font-size="14" fill="var(--axis-dark,#33475b)">270°</text>
<line x1="238.4" y1="35.0" x2="238.4" y2="407.4" stroke="var(--grid,#ffffff)" stroke-opacity="0.16"/>
<text x="238.4" y="431.4" text-anchor="middle" font-size="14" fill="var(--axis-dark,#33475b)">275°</text>
<line x1="391.9" y1="35.0" x2="391.9" y2="407.4" stroke="var(--grid,#ffffff)" stroke-opacity="0.16"/>
<text x="391.9" y="431.4" text-anchor="middle" font-size="14" fill="var(--axis-dark,#33475b)">280°</text>
<line x1="545.3" y1="35.0" x2="545.3" y2="407.4" stroke="var(--grid,#ffffff)" stroke-opacity="0.16"/>
<text x="545.3" y="431.4" text-anchor="middle" font-size="14" fill="var(--axis-dark,#33475b)">285°</text>
<line x1="698.8" y1="35.0" x2="698.8" y2="407.4" stroke="var(--grid,#ffffff)" stroke-opacity="0.16"/>
<text x="698.8" y="431.4" text-anchor="middle" font-size="14" fill="var(--axis-dark,#33475b)">290°</text>
<line x1="852.2" y1="35.0" x2="852.2" y2="407.4" stroke="var(--grid,#ffffff)" stroke-opacity="0.16"/>
<text x="852.2" y="431.4" text-anchor="middle" font-size="14" fill="var(--axis-dark,#33475b)">295°</text>
<text x="85.0" y="453.4" text-anchor="middle" font-size="16" font-weight="700" fill="var(--axis-dark,#1c2733)">W</text>
<text x="775.5" y="453.4" text-anchor="middle" font-size="16" font-weight="700" fill="var(--axis-dark,#1c2733)">WNW</text>
<text x="530.0" y="473.4" text-anchor="middle" font-size="14" fill="var(--axis-dark,#4a5b6b)">Azimut (von Nord über Ost) &#8594;</text>
<text x="24" y="221.2" text-anchor="middle" font-size="14" fill="var(--axis-dark,#33475b)" transform="rotate(-90 24 221.2)">Höhenwinkel</text>
<polygon points="85.0,407.4 85.0,346.5 131.0,337.3 170.9,334.7 238.4,357.0 330.5,370.1 453.3,380.0 576.0,386.6 698.8,391.0 821.6,393.1 975.0,394.2 975.0,407.4" fill="var(--ridge,#0d1a14)" stroke="var(--ridge-line,#20402f)" stroke-width="1"/>
<line x1="85.0" y1="407.4" x2="975.0" y2="407.4" stroke="var(--horizon,#8fb3d6)" stroke-width="1.5"/>
<text x="170.9" y="326.7" text-anchor="middle" font-size="13" fill="var(--peak,#bfe0c8)">Gr. Feldberg 879 m</text>
<polyline points="318.0,120.6 405.0,172.9 490.9,223.9 577.0,274.0 626.1,301.9 710.0,347.8 780.2,383.0 837.7,407.4" fill="none" stroke="var(--track,#ffd36b)" stroke-width="2.5" stroke-opacity="0.6" stroke-dasharray="1 5" stroke-linecap="round"/>
<polyline points="837.7,407.4 862.9,417.6 886.7,430.2 904.6,443.1 925.6,460.1" fill="none" stroke="var(--track-below,#8a8f96)" stroke-width="1.8" stroke-opacity="0.7" stroke-dasharray="4 4" stroke-linecap="round"/>
<circle cx="318.0" cy="120.6" r="18.0" fill="none" stroke="var(--sun-rim,#ffe9a8)" stroke-width="1" stroke-opacity="0.5" stroke-dasharray="2 3"/>
<circle cx="318.0" cy="120.6" r="18.0" fill="var(--sun,#ffcf3f)" mask="url(#sofimask0)"/>
<circle cx="405.0" cy="172.9" r="18.0" fill="none" stroke="var(--sun-rim,#ffe9a8)" stroke-width="1" stroke-opacity="0.5" stroke-dasharray="2 3"/>
<circle cx="405.0" cy="172.9" r="18.0" fill="var(--sun,#ffcf3f)" mask="url(#sofimask1)"/>
<circle cx="490.9" cy="223.9" r="18.0" fill="none" stroke="var(--sun-rim,#ffe9a8)" stroke-width="1" stroke-opacity="0.5" stroke-dasharray="2 3"/>
<circle cx="490.9" cy="223.9" r="18.0" fill="var(--sun,#ffcf3f)" mask="url(#sofimask2)"/>
<circle cx="577.0" cy="274.0" r="18.0" fill="none" stroke="var(--sun-rim,#ffe9a8)" stroke-width="1" stroke-opacity="0.5" stroke-dasharray="2 3"/>
<circle cx="577.0" cy="274.0" r="18.0" fill="var(--sun,#ffcf3f)" mask="url(#sofimask3)"/>
<circle cx="626.1" cy="301.9" r="18.0" fill="none" stroke="var(--sun-rim,#ffe9a8)" stroke-width="1" stroke-opacity="0.5" stroke-dasharray="2 3"/>
<circle cx="626.1" cy="301.9" r="18.0" fill="var(--sun,#ffcf3f)" mask="url(#sofimask4)"/>
<circle cx="710.0" cy="347.8" r="18.0" fill="none" stroke="var(--sun-rim,#ffe9a8)" stroke-width="1" stroke-opacity="0.5" stroke-dasharray="2 3"/>
<circle cx="710.0" cy="347.8" r="18.0" fill="var(--sun,#ffcf3f)" mask="url(#sofimask5)"/>
<circle cx="780.2" cy="383.0" r="18.0" fill="none" stroke="var(--sun-rim,#ffe9a8)" stroke-width="1" stroke-opacity="0.5" stroke-dasharray="2 3"/>
<circle cx="780.2" cy="383.0" r="18.0" fill="var(--sun,#ffcf3f)" mask="url(#sofimask6)"/>
<circle cx="837.7" cy="407.4" r="18.0" fill="none" stroke="var(--sun-rim,#ffe9a8)" stroke-width="1" stroke-opacity="0.5" stroke-dasharray="2 3"/>
<circle cx="837.7" cy="407.4" r="18.0" fill="var(--sun,#ffcf3f)" mask="url(#sofimask7)"/>
<circle cx="925.6" cy="460.1" r="7" fill="none" stroke="#1c2733" stroke-width="1.5" stroke-dasharray="3 2"/>
<text x="318.0" y="63.6" text-anchor="middle" font-size="15" font-weight="700" fill="var(--label,#ffffff)">Beginn</text>
<text x="318.0" y="82.6" text-anchor="middle" font-size="13.5" fill="var(--label-2,#f3f9ff)">19:19 MESZ</text>
<text x="626.1" y="244.9" text-anchor="middle" font-size="15" font-weight="700" fill="var(--label,#ffffff)">Maximum</text>
<text x="626.1" y="263.9" text-anchor="middle" font-size="13.5" fill="var(--label-2,#f3f9ff)">20:13 MESZ</text>
<text x="837.7" y="350.4" text-anchor="middle" font-size="15" font-weight="700" fill="var(--label,#ffffff)">Sonnenuntergang</text>
<text x="837.7" y="369.4" text-anchor="middle" font-size="13.5" fill="var(--label-2,#f3f9ff)">20:49 MESZ</text>
<text x="405.0" y="146.9" text-anchor="middle" font-size="12.5" fill="var(--label-2,#e6f0fa)">19:35</text>
<text x="490.9" y="197.9" text-anchor="middle" font-size="12.5" fill="var(--label-2,#e6f0fa)">19:50</text>
<text x="710.0" y="321.8" text-anchor="middle" font-size="12.5" fill="var(--label-2,#e6f0fa)">20:28</text>
<text x="626.1" y="353.9" text-anchor="middle" font-size="16" font-weight="700" fill="var(--accent,#e8a33d)">88 % bedeckt</text>
<text x="499.3" y="503.4" text-anchor="middle" font-size="14.5" font-weight="700" fill="#000000">Nach Sonnenuntergang (rechnerisch, nicht mehr sichtbar)</text>
<text x="499.3" y="525.4" text-anchor="middle" font-size="13" fill="#000000">Ende der Bedeckung: 21:04 MESZ &#183; Az 297° &#183; −2,9° unter dem Horizont</text>
</svg>
    <figcaption>
      Bahn der Sonne während der Finsternis (Azimut nach Höhenwinkel).
      Sonnenscheiben stark vergrößert und nicht maßstäblich; Bedeckung und Ausrichtung
      der Mondscheibe sind jedoch lagerichtig. Taunuskamm schematisch, reale Gipfel verankert
      (Gr. Feldberg ≈ Az 273°). Ein tatsächliches Horizontprofil vom Beobachtungsort
      sollte vor Ort geprüft werden. Die eingezeichnete Bahn ist refraktionskorrigiert;
      da die Refraktion zum Horizont hin überproportional zunimmt, krümmt sich die
      gestrichelte Linie unterhalb des Horizonts sichtbar stärker - das ist also kein
      Darstellungsfehler, sondern genau dieser Effekt.
    </figcaption>
  </figure>

  <h2>Kontaktzeiten und Sonnenstand</h2>
  <table>
    <thead>
      <tr><th>Ereignis</th><th>Zeit (MESZ)</th><th>Azimut</th><th>Höhe</th><th>Bedeckung</th></tr>
    </thead>
    <tbody>
      <tr><td>1. Kontakt - Beginn</td><td>19:19:49</td><td>277,6° (W)</td><td>13,0°</td><td>0 %</td></tr>
      <tr class="max"><td>Maximum</td><td>20:13:31</td><td>287,6° (WNW)</td><td>4,6°</td><td>88,1 % (Größe 0,90)</td></tr>
      <tr><td>Sonnenuntergang</td><td>20:48:21</td><td>294,3°</td><td>≈ 0°</td><td>≈ 21 %</td></tr>
      <tr class="muted"><td>4. Kontakt - Ende (rechnerisch)</td><td>21:04:33</td><td>297,4°</td><td>− 2,9°</td><td>0 %</td></tr>
    </tbody>
  </table>

  <h2>Worauf es bei der Beobachtung ankommt</h2>
  <p>Weil die Sonne während der gesamten Finsternis so tief steht, entscheidet der Blick
     nach Westen über alles. Freie Sicht „nach Westen“ reicht dabei nicht ganz - wichtig ist
     vor allem der schmale Sektor zwischen <strong>278° und 294°</strong>, also
     <strong>West bis Westnordwest</strong>. Schon eine Baumkante oder ein Hügel von nur
     <strong>2° Höhe</strong> würde die Beobachtung gut 15 Minuten vor dem Ende beenden. Wer
     es genau wissen möchte, nimmt am besten <strong>Kompass und Neigungsmesser</strong> mit
     zum Beobachtungsplatz und vergleicht die Horizontlinie mit den Werten aus dieser Grafik -
     gerade bei einem so flachen Ereignis lohnt sich der Aufwand. Der Große Feldberg liegt bei
     Azimut 273° - ein Stück südlich der Sonne, die rechts davon über niedrigerem
     Hintertaunus-Gelände untergeht.</p>

  <p><strong>Ungeschützt in die Sonne schauen sollten wir zu keinem Zeitpunkt</strong> - auch
     nicht kurz durch Sucher, Fernglas oder Teleskop. Selbst bei 88 % Bedeckung bleibt der
     Rest blendend hell und gefährlich für Auge wie Optik, auch wenn die tiefstehende Sonne
     weniger grell wirkt als sonst. Eine zertifizierte <strong>SoFi-Brille</strong> für den
     freien Blick und ein passender <strong>Objektivfilter</strong> für Fernglas oder Teleskop
     gehören deshalb durchgehend zur Grundausstattung - improvisierte Lösungen bitte nicht.
     Mehr dazu auf unserer Seite <a href="/beobachten">Sicher beobachten</a>.</p>

  <p>Kurz zu den Zahlen: Alle Uhrzeiten sind in <strong>MESZ</strong> (UTC+2), die
     Höhenangaben refraktionskorrigiert (15 °C, 990 hPa) - gerade knapp über dem Horizont
     hängt die tatsächliche Sichtbarkeit aber stark vom genauen Standort und vom Dunst am
     Abendhimmel ab. „Größe“ meint den bedeckten Anteil des Sonnendurchmessers, „Bedeckung“
     den bedeckten Flächenanteil - deshalb weichen beide Werte leicht voneinander ab. Wer
     einen eigenen Beobachtungsplatz im Taunus sucht oder kennt: Auf den anderen Seiten dieser
     Website sammeln wir genau solche Standorte samt Horizonteinschätzung.</p>

  <p class="muted"><em>Clear Skies - wir wünschen einen wolkenfreien Abend am 12. August!</em></p>
</section>
<?php require __DIR__ . '/inc/footer.php';

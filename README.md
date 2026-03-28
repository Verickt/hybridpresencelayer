# Hybrid Presence Layer

**Die soziale Schicht fuer hybride Events.**

> *Ein Raum, nicht zwei Bildschirme.*

---

## Was ist das Problem?

Ein Talk endet. Vor Ort beginnt das Networking — der Raum ist voller Moeglichkeiten. Menschen drehen sich zueinander, in der Schlange, beim Kaffee entstehen spontane Gespraeche.

Remote? Der Stream endet, der Tab schliesst sich, die Sichtbarkeit verschwindet. Der soziale Moment geht verloren.

**Hybrid verliert nicht den Inhalt. Hybrid verliert den Anschluss danach.**

Der wertvollste Teil einer Konferenz passiert nicht auf der Buehne. Flurgespraeche, Zufallsbegegnungen und «weak ties» entscheiden oft ueber den eigentlichen Mehrwert. Die Energie ist fuer alle gleichzeitig hoch — genau dann waere Verbindung am leichtesten. Aber es gibt keinen sozialen Layer. Streaming verteilt Information, es orchestriert keine Begegnung.

**Unsere These: Wenn wir den Moment nach dem Talk richtig gestalten, wird aus Content Connection.**

> Siehe auch: [Praesentationsfolien](presentation/) fuer den vollstaendigen Pitch mit App-Screenshots.

---

## Was baut Hybrid Presence Layer?

Nicht eine generische Event-App, sondern einen sozialen Layer fuer den heissesten Moment im Event: **das 15-Minuten-Fenster nach einer Session**.

```
 +0 Min          +2 Min              +5 Min            +15 Min
 Talk endet      Kontext taucht      Match entsteht    Verbindung steht
                 auf
 Menschen        Wer war im selben   Mutual opt-in     Kurzcall oder
 verlassen den   Talk? Wer hat       sicher, dass      Follow-up, solange
 Content-Modus   dieselben           das Gespraech     der gemeinsame
 und werden      Interessen?         einen klaren      Kontext noch
 wieder offen    Wer ist gerade      Grund hat.        warm ist.
 fuer Kontakte.  verfuegbar?
```

HPL macht aus dem kurzen Fenster nach einer Session einen gezielt orchestrierten Verbindungs-Moment.

---

## Der Flow — in 20 Sekunden vom Kontext zum Gespraech

Die gesamte User Journey ist auf minimale Friction und maximale Relevanz ausgelegt. So laeuft ein typischer Durchgang:

### 1. Einstieg — kein Passwort, kein Account-Chaos

Der Organizer verschickt einen Event-Link per E-Mail oder zeigt einen QR-Code am Veranstaltungsort. Teilnehmende klicken den **Magic Link** — kein Passwort, kein Registrierungsformular, keine App-Installation. HPL ist eine **PWA**: direkt im Browser, keine App-Store-Huerde.

Nach dem Klick: Typ waehlen (*«Ich bin vor Ort»* oder *«Ich bin remote dabei»*), drei Interessen-Tags aus der Event-Tag-Cloud waehlen (z.B. «Zero Trust», «Cloud Migration», «DevOps»), optional einen Icebreaker beantworten. **Unter 60 Sekunden zur Teilnahme.**

> Screenshot: [Login & Onboarding Flow](presentation/slide-6.png)

### 2. RIGHT NOW — eine konkrete Person statt hundert Namen

Der **Praesenz-Feed** beantwortet genau eine Frage: *Wen sollte ich jetzt ansprechen?*

Nicht Suche, sondern Entscheidungshilfe. RIGHT NOW zeigt eine Person, die im aktuellen Moment relevant ist — shared interest und shared context machen sofort klar, warum dieses Gespraech Sinn ergibt. Jede Karte zeigt: Name, physisch/remote-Indikator, aktuelle Session oder Booth, gemeinsame Tags, Aktivitaetspuls.

Ein naechster Schritt, kein offenes Ende.

> Screenshot: [RIGHT NOW Feed](presentation/slide-5.png) | [Feed Detail](presentation/slide-7.png)

### 3. Ping — der erste Schritt ohne Risiko

Ein Tap auf **Ping**. Kein Text, kein Commitment. Reines Signal: *«Ich bin interessiert.»*

Die andere Person sieht den Ping und kann zurueckpingen. Erst bei **gegenseitigem Ping** entsteht ein Match — dann oeffnen sich Chat und 3-Minuten-Call. Keine unerwuenschten Nachrichten, kein Spam. Anti-Harassment by Design.

### 4. It's a Match — sofort ins Gespraech

Beide Seiten sehen einen Grund. Der Match-Screen zeigt die gemeinsamen Interessen und den gemeinsamen Kontext (gleicher Talk, gleiche Tags). Von hier aus: direkt ins kurze Gespraech.

> Screenshot: [Match & Call Flow](presentation/check-4.png)

### 5. 3-Minuten-Call — das digitale Hallengespreach

**Zeitlich begrenzt = risikolos.** 3 Minuten spiegeln die natuerliche Laenge eines Hallengespraechs. Ein Countdown-Timer haelt das Gespraech fokussiert. Eine Icebreaker-Frage startet die Konversation.

Nach 3 Minuten: verlængern (+3 Min, max. 9 total) oder beenden. Danach werden digitale Kontaktkarten mit vollem Kontext ausgetauscht — wer sich getroffen hat, wo (welche Session), was sie gemeinsam haben.

> Screenshot: [3-Minuten-Call](presentation/slide-5.png) (rechts im Bild)

### 6. Sessions — gleicher Talk, gleicher Moment, sofort connecten

Teilnehmende checken per **QR-Scan** in eine Session ein. Sie sehen, wer noch dabei ist — aufgeteilt nach physisch und remote. **Live-Reaktionen** fliessen ueber beide Welten (💡👏🔥), sodass auch remote Teilnehmende die Energie im Raum spueren. Jeder kann **Q&A-Fragen** stellen, die von der gesamten Audience hochgevoted werden.

Nach der Session das Kernfeature: **Post-Session-Matching**. HPL oeffnet ein 15-Minuten-Fenster und schlaegt Teilnehmende vor, die im selben Talk waren und aehnliche Interessen haben. *«4 Leute in dieser Session teilen deine Interessen — jetzt verbinden?»*

> Screenshot: [Post-Session-Fenster](presentation/check-5.png)

### 7. Booths — der gleiche Booth fuer vor Ort und remote

Booths werden kontextuell vorgeschlagen und sind fuer beide Welten gleich zugaenglich. Remote-Besucher generieren **dieselben Lead-Daten** wie physische Besucher. Booth-Staff kann aktiv reagieren, Leads synchronisieren und sieht, welche Sessions Besucher zu ihrem Booth getrieben haben.

**Lead-Capture wird messbar hybrid.** Session-to-Booth-Attribution zeigt: *«60% deiner Hot Leads kamen aus der Zero Trust Session.»*

### 8. Organizer-Dashboard — sehen, steuern, beweisen

Organisatoren sehen **Echtzeit-Metriken**: aktive Teilnehmende (physisch + remote), Verbindungen, Cross-Pollination-Rate (Anteil der Verbindungen, die die physisch/remote-Grenze ueberschreiten). Nicht Bauchgefuehl, sondern messbare Verbindungen zwischen Menschen, Sessions und Booths.

Steuerungswerkzeuge:
- **Serendipity-Wave** ausloesen — alle bekommen gleichzeitig einen unerwarteten Match-Vorschlag
- Booths boosten, die unterdurchschnittlich performen
- Ankuendigungen an alle Teilnehmenden senden

Zielwerte: >60% Aktivierung, <10 Min bis zur ersten Interaktion, >35% Cross-Pollination, >15% Match-Akzeptanz.

---

## Kontext statt GPS

HPL braucht keine permanente Ortung. Das System arbeitet mit sauberen, freiwilligen Signalen:

| Signal | Beschreibung |
|--------|-------------|
| **Eintritt** | Link oder QR — Teilnehmende kommen ins Event und waehlen vor Ort oder remote |
| **Session** | QR-Scan oder Tap — *«Ich bin hier»* setzt den gemeinsamen Session-Kontext |
| **Booth** | Booth-Besuche werden erkannt, aktiv, nicht ueber unsichtbares Tracking |
| **Live-Status** | Available, In Session, At Booth, Busy oder Away |

Was die Plattform speichert: nicht den exakten Standort, sondern Kontext — Session, Booth, Teilnehmer-Typ und Dauer. Was das Dashboard daraus macht: es zeigt live, wer wo ist und ob die Touchpoints spaeter reale Begegnungen ausgeloest haben.

---

## Sicherheit und Respekt

- Kein unaufgeforderter Chat — Kommunikation erfordert gegenseitigen Match
- Ping traegt keine Nachricht — kein unerwuenschter Text moeglich
- Rate-Limiting: max. 10 Pings pro Stunde
- Block- und Report-Funktion
- Cooldown nach 3 ignorierten Pings
- **Invisible-Modus** jederzeit verfuegbar
- Keine GPS-Daten, kein Behavioral Tracking

---

## Tech Stack

| Komponente | Technologie |
|------------|-------------|
| Backend | Laravel 13 (PHP 8.4) |
| Frontend | Vue 3 + Inertia.js v3 |
| Echtzeit | Laravel Reverb (WebSockets), 18 Broadcast-Events |
| Video | WebRTC (Peer-to-Peer, kein externer Service) |
| Styling | Tailwind CSS v4, Mobile-first |
| Auth | Magic Links (passwortlos) via Laravel Fortify |
| Deployment | PWA — kein App Store, sofortiger Zugang per URL/QR |
| Testing | Pest v4 (PHP), Playwright (Browser) |

---

## Dokumentation & Materialien

| Dokument | Pfad |
|----------|------|
| Praesentation (Slidev, 11 Folien) | [`presentation/`](presentation/) |
| App-Screenshots (Flows) | [`presentation/slide-*.png`](presentation/) |
| Design-Katalog (alle UI-Screens) | [`docs/designs/design-catalog.html`](docs/designs/design-catalog.html) |
| Praesentations-Brief fuer die Jury | [`docs/mvp/PRESENTATION-BRIEF.md`](docs/mvp/PRESENTATION-BRIEF.md) |
| Demo-Script | [`docs/mvp/DEMO-SCRIPT.md`](docs/mvp/DEMO-SCRIPT.md) |
| MVP-Scope (Build vs. Stub) | [`docs/mvp/13-mvp-scope.md`](docs/mvp/13-mvp-scope.md) |
| Feature-Specs (13 Kapitel) | [`docs/mvp/`](docs/mvp/) |

---

## MVP-Status: Feature-Pruefung

Jede in dieser README erwæhnte Aktion wurde gegen den aktuellen Codestand geprueft.

| Feature | Status | Details |
|---------|--------|---------|
| Magic Link (passwortlos) | ✅ Implementiert | SHA-256-Token, 1h Throttling, automatische Weiterleitung |
| Onboarding (Typ + Tags + Icebreaker) | ✅ Implementiert | 4-Schritt-Wizard, 3–5 Tags, event-spezifisch |
| PWA (kein App Store) | ⚠️ Teilweise | Dynamisches Manifest pro Event; kein Service Worker fuer Offline |
| Praesenz-Feed (RIGHT NOW) | ✅ Implementiert | Tag-Intersection-Scoring, Filter nach Typ/Status/Tags |
| Kontextbasierte Vorschlaege | ✅ Implementiert | SuggestionService mit Session-Affinitaet und Tag-Overlap |
| One-Tap Ping | ✅ Implementiert | Rate-Limiting (10/h), Duplikat-Schutz, Block-Check, 3-Ignore-Cooldown |
| Gegenseitiger Match | ✅ Implementiert | Erzeugt automatisch Connection, Broadcast an beide Seiten |
| Chat nach Match | ✅ Implementiert | Echtzeit-Nachrichten ueber WebSocket-Kanal |
| 3-Minuten-Videocall | ✅ Implementiert | WebRTC, Countdown-Timer, Room-UUID |
| Call-Verlaengerung (max. 9 Min) | ✅ Implementiert | Bis zu 2 Extensions mit Tracking |
| Icebreaker-Frage im Call | ✅ Implementiert | Erfasst im Onboarding, angezeigt im Call |
| Digitale Kontaktkarten | ⚠️ Teilweise | Model existiert; automatischer Austausch nach Call nicht explizit verkabelt |
| Session-Check-in (QR) | ✅ Implementiert | Signatur-Validierung, Check-in/Check-out mit Statusupdate |
| Live-Reaktionen | ✅ Implementiert | 5 Typen, Echtzeit-Broadcast, Moderations-Graph |
| Q&A mit Upvoting | ✅ Implementiert | Fragen, Replies, Votes, Pinning/Hiding durch Moderatoren |
| Post-Session-Matching (15 Min) | ✅ Implementiert | Engagement-Score (60% Reactions, 40% Q&A), automatisches Expiry |
| Booth-Besuche (physisch + remote) | ✅ Implementiert | Visit-Tracking, anonyme + benannte Besuche |
| Booth-Staff-Tools (Leads, Ping) | ✅ Implementiert | Lead-Capture, CSV-Export, Announcements, Visitor-Dashboard |
| Organizer-Dashboard | ✅ Implementiert | Overview-Stats, Session-Analytics, Booth-Performance |
| Serendipity-Wave | ✅ Implementiert | Mass-Suggestion fuer alle aktiven Teilnehmenden |
| Serendipity-Modus (Opt-in) | ✅ Implementiert | Cross-Discipline-Matching ueber SuggestionService |
| Booth-Boost | ✅ Implementiert | Organizer-Aktion fuer unterdurchschnittliche Booths |
| Anti-Harassment (Block/Report) | ✅ Implementiert | Block-Model, Report-Model, Rate-Limiting, Cooldown |
| Invisible-Modus | ✅ Implementiert | Toggle mit Broadcast, Feed filtert unsichtbare User |
| WebSockets (Reverb) | ✅ Implementiert | 18 Events, 6 Kanaltypen |
| Cross-World-Gleichheit | ✅ Implementiert | `is_cross_world` auf Connections, gleiche Feeds/Booths/Sessions |

**Ergebnis: 24 von 26 Features voll implementiert, 2 teilweise (PWA-Offline, Kontaktkarten-Automatik).**

---

## Was wir eigentlich bauen

Ein hybrides Event, das sich wieder wie **ein gemeinsamer Raum** anfuehlt.

Sichtbarkeit / Relevanz / Verbindungen.

---

*Rick / Maerz 2026 — riccardo.previti@uoiea.ch*

*Gebaut mit Laravel, Vue 3, Inertia.js, Laravel Reverb und WebRTC.*

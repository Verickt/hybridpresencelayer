# Hybrid Presence Layer

**Die soziale Schicht für hybride Events.**

> *Ein Raum, nicht zwei Bildschirme.*

---

## Was ist das Problem?

Ein Talk endet. Vor Ort beginnt das Networking — der Raum ist voller Möglichkeiten. Menschen drehen sich zueinander, in der Schlange, beim Kaffee entstehen spontane Gespräche.

Remote? Der Stream endet, der Tab schliesst sich, die Sichtbarkeit verschwindet. Der soziale Moment geht verloren.

**Hybrid verliert nicht den Inhalt. Hybrid verliert den Anschluss danach.**

Der wertvollste Teil einer Konferenz passiert nicht auf der Bühne. Flurgespräche, Zufallsbegegnungen und «weak ties» entscheiden oft über den eigentlichen Mehrwert. Die Energie ist für alle gleichzeitig hoch — genau dann wäre Verbindung am leichtesten. Aber es gibt keinen sozialen Layer. Streaming verteilt Information, es orchestriert keine Begegnung.

**Unsere These: Wenn wir den Moment nach dem Talk richtig gestalten, wird aus Content Connection.**

> Siehe auch: [Präsentationsfolien](presentation/) für den vollständigen Pitch mit App-Screenshots.

---

## Was baut Hybrid Presence Layer?

Nicht eine generische Event-App, sondern einen sozialen Layer für den heissesten Moment im Event: **das 15-Minuten-Fenster nach einer Session**.

```
 +0 Min          +2 Min              +5 Min            +15 Min
 Talk endet      Kontext taucht      Match entsteht    Verbindung steht
                 auf
 Menschen        Wer war im selben   Mutual opt-in     Kurzcall oder
 verlassen den   Talk? Wer hat       sicher, dass      Follow-up, solange
 Content-Modus   dieselben           das Gespräch     der gemeinsame
 und werden      Interessen?         einen klaren      Kontext noch
 wieder offen    Wer ist gerade      Grund hat.        warm ist.
 für Kontakte.  verfügbar?
```

HPL macht aus dem kurzen Fenster nach einer Session einen gezielt orchestrierten Verbindungs-Moment.

---

## Der Flow — in 20 Sekunden vom Kontext zum Gespräch

Die gesamte User Journey ist auf minimale Friction und maximale Relevanz ausgelegt. So läuft ein typischer Durchgang:

### 1. Einstieg — kein Passwort, kein Account-Chaos

Der Organizer verschickt einen Event-Link per E-Mail oder zeigt einen QR-Code am Veranstaltungsort. Teilnehmende klicken den **Magic Link** — kein Passwort, kein Registrierungsformular, keine App-Installation. HPL ist eine **PWA**: direkt im Browser, keine App-Store-Hürde.

Nach dem Klick: Typ wählen (*«Ich bin vor Ort»* oder *«Ich bin remote dabei»*), drei Interessen-Tags aus der Event-Tag-Cloud wählen (z.B. «Zero Trust», «Cloud Migration», «DevOps»), optional einen Icebreaker beantworten. **Unter 60 Sekunden zur Teilnahme.**

> Screenshot: [Login & Onboarding Flow](presentation/slide-6.png)

### 2. RIGHT NOW — eine konkrete Person statt hundert Namen

Der **Präsenz-Feed** beantwortet genau eine Frage: *Wen sollte ich jetzt ansprechen?*

Nicht Suche, sondern Entscheidungshilfe. RIGHT NOW zeigt eine Person, die im aktuellen Moment relevant ist — shared interest und shared context machen sofort klar, warum dieses Gespräch Sinn ergibt. Jede Karte zeigt: Name, physisch/remote-Indikator, aktuelle Session oder Booth, gemeinsame Tags, Aktivitätspuls.

Ein nächster Schritt, kein offenes Ende.

> Screenshot: [RIGHT NOW Feed](presentation/slide-5.png) | [Feed Detail](presentation/slide-7.png)

### 3. Ping — der erste Schritt ohne Risiko

Ein Tap auf **Ping**. Kein Text, kein Commitment. Reines Signal: *«Ich bin interessiert.»*

Die andere Person sieht den Ping und kann zurückpingen. Erst bei **gegenseitigem Ping** entsteht ein Match — dann öffnen sich Chat und 3-Minuten-Call. Keine unerwünschten Nachrichten, kein Spam. Anti-Harassment by Design.

### 4. It's a Match — sofort ins Gespräch

Beide Seiten sehen einen Grund. Der Match-Screen zeigt die gemeinsamen Interessen und den gemeinsamen Kontext (gleicher Talk, gleiche Tags). Von hier aus: direkt ins kurze Gespräch.

> Screenshot: [Match & Call Flow](presentation/check-4.png)

### 5. 3-Minuten-Call — das digitale Hallengespräch

**Zeitlich begrenzt = risikolos.** 3 Minuten spiegeln die natürliche Länge eines Hallengesprächs. Ein Countdown-Timer hält das Gespräch fokussiert. Eine Icebreaker-Frage startet die Konversation.

Nach 3 Minuten: verlängern (+3 Min, max. 9 total) oder beenden. Danach werden digitale Kontaktkarten mit vollem Kontext ausgetauscht — wer sich getroffen hat, wo (welche Session), was sie gemeinsam haben.

> Screenshot: [3-Minuten-Call](presentation/slide-5.png) (rechts im Bild)

### 6. Sessions — gleicher Talk, gleicher Moment, sofort connecten

Teilnehmende checken per **QR-Scan** in eine Session ein. Sie sehen, wer noch dabei ist — aufgeteilt nach physisch und remote. **Live-Reaktionen** fliessen über beide Welten (💡👏🔥), sodass auch remote Teilnehmende die Energie im Raum spüren. Jeder kann **Q&A-Fragen** stellen, die von der gesamten Audience hochgevoted werden.

Nach der Session das Kernfeature: **Post-Session-Matching**. HPL öffnet ein 15-Minuten-Fenster und schlägt Teilnehmende vor, die im selben Talk waren und ähnliche Interessen haben. *«4 Leute in dieser Session teilen deine Interessen — jetzt verbinden?»*

> Screenshot: [Post-Session-Fenster](presentation/check-5.png)

### 7. Booths — der gleiche Booth für vor Ort und remote

Booths werden kontextuell vorgeschlagen und sind für beide Welten gleich zugänglich. Remote-Besucher generieren **dieselben Lead-Daten** wie physische Besucher. Booth-Staff kann aktiv reagieren, Leads synchronisieren und sieht, welche Sessions Besucher zu ihrem Booth getrieben haben.

**Lead-Capture wird messbar hybrid.** Session-to-Booth-Attribution zeigt: *«60% deiner Hot Leads kamen aus der Zero Trust Session.»*

### 8. Organizer-Dashboard — sehen, steuern, beweisen

Organisatoren sehen **Echtzeit-Metriken**: aktive Teilnehmende (physisch + remote), Verbindungen, Cross-Pollination-Rate (Anteil der Verbindungen, die die physisch/remote-Grenze überschreiten). Nicht Bauchgefühl, sondern messbare Verbindungen zwischen Menschen, Sessions und Booths.

Steuerungswerkzeuge:
- **Serendipity-Wave** auslösen — alle bekommen gleichzeitig einen unerwarteten Match-Vorschlag
- Booths boosten, die unterdurchschnittlich performen
- Ankündigungen an alle Teilnehmenden senden

Zielwerte: >60% Aktivierung, <10 Min bis zur ersten Interaktion, >35% Cross-Pollination, >15% Match-Akzeptanz.

---

## Kontext statt GPS

HPL braucht keine permanente Ortung. Das System arbeitet mit sauberen, freiwilligen Signalen:

| Signal | Beschreibung |
|--------|-------------|
| **Eintritt** | Link oder QR — Teilnehmende kommen ins Event und wählen vor Ort oder remote |
| **Session** | QR-Scan oder Tap — *«Ich bin hier»* setzt den gemeinsamen Session-Kontext |
| **Booth** | Booth-Besuche werden erkannt, aktiv, nicht über unsichtbares Tracking |
| **Live-Status** | Available, In Session, At Booth, Busy oder Away |

Was die Plattform speichert: nicht den exakten Standort, sondern Kontext — Session, Booth, Teilnehmer-Typ und Dauer. Was das Dashboard daraus macht: es zeigt live, wer wo ist und ob die Touchpoints später reale Begegnungen ausgelöst haben.

---

## Sicherheit und Respekt

- Kein unaufgeforderter Chat — Kommunikation erfordert gegenseitigen Match
- Ping trägt keine Nachricht — kein unerwünschter Text möglich
- Rate-Limiting: max. 10 Pings pro Stunde
- Block- und Report-Funktion
- Cooldown nach 3 ignorierten Pings
- **Invisible-Modus** jederzeit verfügbar
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
| Präsentation (Slidev, 11 Folien) | [`presentation/`](presentation/) |
| App-Screenshots (Flows) | [`presentation/slide-*.png`](presentation/) |
| Präsentations-Brief für die Jury | [`docs/mvp/PRESENTATION-BRIEF.md`](docs/mvp/PRESENTATION-BRIEF.md) |
| Demo-Script | [`docs/mvp/DEMO-SCRIPT.md`](docs/mvp/DEMO-SCRIPT.md) |
| MVP-Scope (Build vs. Stub) | [`docs/mvp/13-mvp-scope.md`](docs/mvp/13-mvp-scope.md) |
| Feature-Specs (13 Kapitel) | [`docs/mvp/`](docs/mvp/) |

---

## MVP-Status: Feature-Prüfung

Jede in dieser README erwähnte Aktion wurde konservativ gegen den aktuellen Codestand geprüft. ✅ = funktioniert End-to-End, ⚠️ = Backend vorhanden aber Frontend unvollständig, ❌ = nicht funktional.

### Einstieg & Onboarding

| Feature | Status | Details |
|---------|--------|---------|
| Magic Link (passwortlos) | ✅ | SHA-256-Token, 1h Throttling, automatische Weiterleitung |
| Onboarding (Typ + Tags + Icebreaker) | ✅ | 4-Schritt-Wizard, 3–5 Tags, event-spezifisch |
| PWA (kein App Store) | ⚠️ | Dynamisches Manifest pro Event vorhanden; kein Service Worker für Offline |

### Entdecken & Verbinden

| Feature | Status | Details |
|---------|--------|---------|
| Präsenz-Feed (RIGHT NOW) | ✅ | Tag-Intersection-Scoring, Filter nach Typ/Status/Tags |
| Kontextbasierte Vorschläge | ✅ | Gewichteter Algorithmus (Interessen 30%, Kontext 25%, Session-Affinität 25%, Verfügbarkeit 20%) |
| One-Tap Ping | ✅ | Rate-Limiting (10/h), Duplikat-Schutz, Block-Check, 3-Ignore-Cooldown |
| Gegenseitiger Match | ✅ | Erzeugt automatisch Connection, Broadcast an beide Seiten |

### Interaktion

| Feature | Status | Details |
|---------|--------|---------|
| Chat nach Match | ✅ | Echtzeit via Echo — HTTP + WebSocket, Typing-Indicator, funktioniert End-to-End |
| 3-Minuten-Videocall | ❌ | **UI-Shell vorhanden** (Kamera, Timer, Extensions), aber **kein WebRTC-Signaling** — zwei Browser können sich nicht verbinden. Nur lokale Kamera sichtbar. |
| Call-Verlängerung (max. 9 Min) | ⚠️ | HTTP-basierte Metadaten-Verwaltung funktioniert, aber ohne funktionierenden Call irrelevant |
| Icebreaker-Frage | ✅ | Erfasst im Onboarding, angezeigt im Call-UI |
| Digitale Kontaktkarten | ❌ | Model existiert, aber **kein Controller, keine Route, kein UI** — nicht implementiert |

### Sessions

| Feature | Status | Details |
|---------|--------|---------|
| Session-Check-in (QR) | ✅ | Signatur-Validierung, Check-in/Check-out mit Statusupdate |
| Live-Reaktionen | ✅ | 5 Typen, Echtzeit-Broadcast via Echo, Cluster-Erkennung funktioniert End-to-End |
| Q&A mit Upvoting | ✅ | Fragen, Replies, Votes, Pinning/Hiding durch Moderatoren |
| Post-Session-Matching (15 Min) | ✅ | Automatisiert via Scheduled Command, Engagement-Score, 15-Min-Fenster |

### Booths

| Feature | Status | Details |
|---------|--------|---------|
| Booth-Besuche | ✅ | Visit-Tracking, anonyme + benannte Besuche, Thread-Broadcasting funktioniert |
| Booth-Staff: Leads & Export | ✅ | Lead-Liste, CSV-Export |
| Booth-Staff: Echtzeit-Besucher | ⚠️ | Visitor-Liste nur bei Page-Load, keine Echtzeit-Updates wenn jemand kommt/geht |
| Booth-Staff: Besucher pingen | ❌ | Kein Ping-Endpoint für Booth-Staff vorhanden |

### Organizer

| Feature | Status | Details |
|---------|--------|---------|
| Dashboard Metriken | ⚠️ | Daten werden bei Page-Load geladen, aber **keine Echtzeit-Updates** — Organizer muss manuell refreshen |
| Serendipity-Wave | ⚠️ | Backend-Endpoint funktioniert, aber keine Echtzeit-Benachrichtigung an Teilnehmende |
| Serendipity-Modus (Opt-in) | ❌ | Matching-Logik existiert im Backend, aber **kein UI-Toggle** für Teilnehmende |
| Booth-Boost | ⚠️ | Endpoint vorhanden, Effekt auf Teilnehmende unklar |
| Ankündigungen | ⚠️ | Backend dispatcht Event, aber **kein Frontend-Listener** — Ankündigungen kommen nie an |

### Sicherheit & Infrastruktur

| Feature | Status | Details |
|---------|--------|---------|
| Anti-Harassment (Block/Report) | ✅ | Block-Model, Report-Model, Rate-Limiting, Cooldown |
| Invisible-Modus | ✅ | Toggle funktioniert, Feed filtert unsichtbare User |
| WebSockets (Reverb) | ⚠️ | Infrastruktur funktioniert für Chat, Reactions, Booth-Threads. Viele Broadcast-Events haben aber **keinen Frontend-Listener** |
| Cross-World-Gleichheit | ✅ | Remote-Teilnehmende in Feed, Booths, Sessions gleichgestellt |

### Zusammenfassung

| | Anzahl |
|---|--------|
| ✅ Funktioniert End-to-End | 15 |
| ⚠️ Teilweise (Backend ok, Frontend fehlt) | 8 |
| ❌ Nicht funktional | 4 (Videocall, Kontaktkarten, Serendipity-Opt-in, Booth-Staff-Ping) |

---

## Was wir eigentlich bauen

Ein hybrides Event, das sich wieder wie **ein gemeinsamer Raum** anfühlt.

Sichtbarkeit / Relevanz / Verbindungen.

---

*Rick / März 2026 — riccardo.previti@uoiea.ch*

*Gebaut mit Laravel, Vue 3, Inertia.js, Laravel Reverb und WebRTC.*

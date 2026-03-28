# Hybrid Presence Platform

**Ein Social Layer in Echtzeit für hybride Events — verbindet physische und remote Teilnehmende, die sich sonst nie begegnet wären.**

---

## Das Problem

Hybride Events schaffen zwei getrennte Welten:

- **Vor-Ort-Teilnehmende** vernetzen sich ganz natürlich — Pausengespräche, zufällige Begegnungen, gemeinsame Reaktionen.
- **Remote-Teilnehmende** sind unsichtbar. Sie schauen zu, aber sie nehmen nicht am sozialen Geschehen teil. Sie sind Zuschauer, keine Teilnehmer.

Die Technologie für Content-Streaming existiert. Die Technologie, um **Beziehungen über diese Grenze hinweg** zu schaffen, fehlt.

**Was fehlt, ist nicht Video — es ist soziale Infrastruktur.**

## Die Lösung

Drei Schichten, eine Plattform:

```
┌─────────────────────────────────┐
│     Interaktions-Schicht        │  Ping, Chat, 3-Minuten-Videocall
├─────────────────────────────────┤
│     Connection Engine           │  Wer sollte wen treffen — genau jetzt
├─────────────────────────────────┤
│     Präsenz-Schicht             │  Wer ist da, wo, und was tut er/sie
└─────────────────────────────────┘
```

Wir sind **keine** Streaming-Plattform, keine Chat-App, kein Event-Management-Tool und kein soziales Netzwerk. Wir sind die **fehlende Schicht dazwischen** — der Mechanismus, der gemeinsame Anwesenheit in echte Verbindungen verwandelt.

## Wie es funktioniert

### Akt 1: Einstieg in unter 60 Sekunden
1. Teilnehmende erhalten einen Event-Link (E-Mail oder QR-Code am Veranstaltungsort)
2. Magic Link klicken — **kein Passwort, kein Registrierungsformular**
3. Auswahl: *«Ich bin vor Ort»* oder *«Ich bin remote dabei»*
4. 3 Interessen-Tags aus der Tag-Cloud wählen (z.B. «Zero Trust», «Cloud Migration», «DevOps»)
5. Fertig — der **Präsenz-Feed** ist live

**Keine App-Installation nötig** (es ist eine PWA).

### Akt 2: Entdecken & Verbinden
6. Der Feed zeigt andere Teilnehmende, sortiert nach Relevanz — gemeinsame Interessen und Kontext
7. Eine **«Jetzt gerade»-Karte** erscheint: *«Du und Lena habt beide Zero Trust getaggt und schaut die gleiche Keynote»*
8. Ein Tap auf **Ping** — kein Text nötig, reines Signal: «Ich bin interessiert»
9. Lena pingt zurück → **Match!** Optionen: Chat starten oder 3-Minuten-Call

### Akt 3: Echte Interaktion
10. **3-Minuten-Videocall** mit Countdown — kurz, fokussiert, risikolos
11. Icebreaker-Frage als Gesprächseinstieg
12. Nach 3 Minuten: verlängern (+3 Min, max. 9 total) oder beenden
13. Danach: digitale Kontaktkarten werden ausgetauscht, mit vollem Kontext

### Akt 4: Sessions & Booths
14. **Session-Check-in** per QR-Scan — sehen, wer noch dabei ist (physisch + remote)
15. **Live-Reaktionen** fliessen über beide Welten (💡👏🔥)
16. **Q&A** — Fragen werden von allen Teilnehmenden hochgevoted
17. **Post-Session-Matching**: *«4 Leute in dieser Session teilen deine Interessen — jetzt verbinden?»*
18. **Booth-Besuche** — Booth-Personal sieht physische und remote Besucher gleichermassen

### Akt 5: Organizer-Dashboard
19. Echtzeit-Metriken: aktive Teilnehmende, Verbindungen, Cross-Pollination-Rate
20. **Serendipity-Wave** auslösen — alle bekommen gleichzeitig einen Match-Vorschlag
21. Booths boosten, Ankündigungen senden, Matching in Echtzeit steuern

## Signature Features

| Feature | Beschreibung |
|---|---|
| **One-Tap Ping** | Kein Text, kein Commitment. Löst das schwerste Problem beim Netzwerken: den ersten Schritt machen. Gegenseitiger Ping nötig — keine unerwünschten Nachrichten. |
| **3-Minuten-Call** | Zeitlich begrenzt = risikolos. Spiegelt die natürliche Länge eines Hallengesprächs. Verlängerbar bei guter Chemie. |
| **Serendipity-Modus** | Opt-in: «Überrasch mich mit jemandem, den ich sonst nie treffen würde.» Matcht bewusst Leute ohne gemeinsame Interessen — die wertvollsten Verbindungen. |
| **Cross-World-Gleichheit** | Remote-Teilnehmende generieren dieselben Lead-Daten, sehen denselben Reaktions-Stream, erscheinen im selben Feed. Keine Zuschauer zweiter Klasse. |
| **Anti-Harassment by Design** | Kein unaufgeforderter Chat (Match nötig), Rate-Limiting, Block/Report, Invisible-Modus. |

## Technologie

- **PWA** — kein App Store, sofortiger Zugang per URL/QR-Code
- **Laravel + Inertia.js + Vue 3** — Server-driven SPA
- **Echtzeit via WebSockets** (Laravel Reverb) — Präsenz-Updates in 1–2 Sekunden
- **WebRTC** — Peer-to-Peer-Videocalls ohne externen Service
- **Mobile-first Design** — Bottom-Tab-Navigation, grosse Tap-Targets

## Drei Akteure

| Akteur | Rolle |
|---|---|
| **Teilnehmende** (physisch + remote) | Entdecken und verbinden sich mit relevanten Personen |
| **Organizer** | Event einrichten, Echtzeit-Dashboard überwachen, Networking steuern |
| **Booth-Personal** | Besucher sehen, proaktiv pingen, Leads erfassen — physisch wie remote |

## Business Value

- **Für Organizer**: Neue KPIs jenseits der Teilnehmerzahl — Networking-Dichte, Cross-Pollination-Rate, Match-Akzeptanz
- **Für Sponsoren**: Leads aus beiden Welten, Session-to-Booth-Attribution, proaktive Kontaktaufnahme
- **Für Teilnehmende**: Null Kosten, null Reibung, unter 60 Sekunden zum Start — und remote Teilnehmende sind endlich gleichwertig

## Zielmarkt

B2B-Konferenzen und Verbandsevents, 200–2'000 Teilnehmende, 10–40% Remote-Anteil. Sweet Spot: Events, bei denen professionelles Networking ein erklärtes Ziel ist.

---

## MVP-Status: Feature-Prüfung

Jede in dieser README erwähnte Aktion/Funktion wurde gegen den aktuellen Codestand geprüft.

### Akt 1: Einstieg

| # | Aktion | Status | Details |
|---|--------|--------|---------|
| 1 | Event-Link per E-Mail oder QR-Code | ✅ Vorhanden | Magic-Link-Versand per E-Mail implementiert; QR-Code am Veranstaltungsort zeigt Join-URL |
| 2 | Magic Link — kein Passwort | ✅ Vorhanden | `MagicLinkController` mit SHA-256-Token, 1h Throttling, automatische Weiterleitung |
| 3 | Auswahl physisch/remote | ✅ Vorhanden | Onboarding-Schritt 1, gespeichert in `event_user`-Pivot |
| 4 | 3 Interessen-Tags wählen | ✅ Vorhanden | Onboarding-Schritt 2, 3–5 Tags erforderlich, event-spezifisch |
| 5 | PWA — keine App-Installation | ⚠️ Teilweise | Dynamisches `manifest.json` pro Event vorhanden; kein dedizierter Service Worker für Offline-Modus gefunden |

### Akt 2: Entdecken & Verbinden

| # | Aktion | Status | Details |
|---|--------|--------|---------|
| 6 | Feed sortiert nach Relevanz | ✅ Vorhanden | `PresenceFeedController` mit Tag-Intersection-Scoring, Filter nach Typ/Status/Tags |
| 7 | «Jetzt gerade»-Karten | ✅ Vorhanden | `SuggestionService` generiert kontextbasierte Vorschläge (gleiche Session, gleiche Tags) |
| 8 | One-Tap Ping | ✅ Vorhanden | `PingService` mit Rate-Limiting (10/h), Duplikat-Prüfung, Block-Check, 3-Ignore-Cooldown |
| 9 | Gegenseitiger Match → Chat/Call | ✅ Vorhanden | Mutual Match erzeugt automatisch `Connection`; Broadcast-Events `PingReceived` + `MutualMatchCreated` |

### Akt 3: Echte Interaktion

| # | Aktion | Status | Details |
|---|--------|--------|---------|
| 10 | 3-Minuten-Videocall mit Countdown | ✅ Vorhanden | WebRTC mit nativer MediaDevices API, Timer, Room-UUID |
| 11 | Icebreaker-Frage | ✅ Vorhanden | Icebreaker-Antwort wird im Onboarding erfasst und bei Calls angezeigt |
| 12 | Verlängern (+3 Min, max 9) | ✅ Vorhanden | Bis zu 2 Extensions, Call-State mit `started_at`, `expires_at`, `extensions` |
| 13 | Digitale Kontaktkarten austauschen | ⚠️ Teilweise | `ContactCard`-Model existiert mit Connection-FK; automatische Erstellung nach Call-Ende nicht explizit in Controllern sichtbar |

### Akt 4: Sessions & Booths

| # | Aktion | Status | Details |
|---|--------|--------|---------|
| 14 | Session-Check-in per QR-Scan | ✅ Vorhanden | QR mit Signatur-Validierung, `PresenceService.checkInToSession()`, Check-in/Check-out-Routes |
| 15 | Live-Reaktionen (💡👏🔥) | ✅ Vorhanden | `SessionReactionController`, 5 Typen, Broadcast `SessionReactionSent`, Echtzeit-Graph in Moderation |
| 16 | Q&A mit Upvoting | ✅ Vorhanden | `SessionQuestion` + `SessionQuestionVote` + Replies, Pinning/Hiding durch Moderatoren |
| 17 | Post-Session-Matching (15 Min) | ✅ Vorhanden | `SessionEngagementEdge` mit Reaction-Sync (60%) + QA-Score (40%), 15-Min-Fenster |
| 18 | Booth-Besuche (physisch + remote) | ✅ Vorhanden | Visit-Tracking, anonyme + benannte Besuche, Booth-Detail mit Visitor-Liste |

### Akt 5: Organizer-Dashboard

| # | Aktion | Status | Details |
|---|--------|--------|---------|
| 19 | Echtzeit-Metriken | ✅ Vorhanden | `DashboardService` mit Overview-Stats, Session-Analytics, Booth-Performance |
| 20 | Serendipity-Wave auslösen | ✅ Vorhanden | `OrganizerActionController.serendipityWave()` generiert Vorschläge für alle aktiven Teilnehmenden |
| 21 | Booths boosten | ✅ Vorhanden | Organizer-Aktionen für Booth-Boost implementiert |

### Signature Features

| Feature | Status | Details |
|---------|--------|---------|
| One-Tap Ping | ✅ Vorhanden | Komplett mit Rate-Limiting, Duplikat-Schutz, Cooldown |
| 3-Minuten-Call | ✅ Vorhanden | WebRTC, Timer, Extensions, Icebreaker |
| Serendipity-Modus | ✅ Vorhanden | Opt-in Matching über `SuggestionService`, bewusst niedrige Überlappung |
| Cross-World-Gleichheit | ✅ Vorhanden | Remote-Teilnehmende in Feed, Booths, Sessions gleichgestellt; `is_cross_world` auf Connections |
| Anti-Harassment | ✅ Vorhanden | Rate-Limiting (10/h), `Block`-Model, `Report`-Model, 3-Ignore-Cooldown, Invisible-Modus |

### Technologie

| Komponente | Status | Details |
|------------|--------|---------|
| PWA | ⚠️ Teilweise | Manifest vorhanden, kein Service Worker für Offline |
| Laravel + Inertia + Vue 3 | ✅ Vorhanden | Vollständiger Stack |
| WebSockets (Reverb) | ✅ Vorhanden | 18 Broadcast-Events, 6 Kanäle (User, Event, Chat, Session, Booth, Notifications) |
| WebRTC | ✅ Vorhanden | Native Browser-API, kein externer Service |
| Mobile-first / Bottom-Tabs | ✅ Vorhanden | `BottomTabs.vue` mit Tab-Navigation |

### Zusammenfassung

- **✅ Voll implementiert**: 23 von 26 Aktionen
- **⚠️ Teilweise**: 3 (PWA-Offline, Kontaktkarten-Automatik, Service Worker)
- **❌ Fehlend**: 0

---

*Gebaut mit Laravel, Vue 3, Inertia.js, Laravel Reverb und WebRTC.*

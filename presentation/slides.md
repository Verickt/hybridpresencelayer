---
theme: default
title: Hybrid Presence Layer
info: |
  Die soziale Schicht für hybride Events.
  Ein Entwickler. KI-unterstützt.
class: text-center
drawings:
  persist: false
transition: slide-left
mdc: true
fonts:
  sans: Inter
  mono: JetBrains Mono
---

<style>
:root {
  --slidev-theme-primary: #4F46E5;
}

.slidev-layout {
  font-family: 'Inter', sans-serif;
}
</style>

# Hybrid Presence Layer

Die soziale Schicht für hybride Events

<div class="pt-12">
  <span class="px-2 py-1 rounded text-sm" style="background: rgba(79,70,229,0.15); color: #6366F1;">
    Ein Raum, nicht zwei Bildschirme
  </span>
</div>

<div class="abs-br m-6 flex gap-2 text-sm opacity-50">
  Rick · März 2026
</div>

---
layout: center
class: text-center
---

# Das Problem

<div class="text-2xl mt-8 max-w-2xl mx-auto leading-relaxed" style="color: #525252;">
Hybride Events schaffen <span style="color: #4F46E5; font-weight: 600;">zwei getrennte Welten</span>.
</div>

<div class="grid grid-cols-2 gap-16 mt-12 max-w-3xl mx-auto">
  <div class="text-left p-6 rounded-xl" style="background: #F5F5F5;">
    <div class="text-xs font-semibold tracking-[0.16em]" style="color: #4F46E5;">VOR ORT</div>
    <div class="font-semibold text-lg mt-3 mb-2">Zu viele Menschen. Zu wenig Orientierung.</div>
    <div style="color: #737373;">Networking ist Zufall. Die richtigen Leute sind im Raum, aber schwer greifbar.</div>
  </div>
  <div class="text-left p-6 rounded-xl" style="background: #F5F5F5;">
    <div class="text-xs font-semibold tracking-[0.16em]" style="color: #4F46E5;">REMOTE</div>
    <div class="font-semibold text-lg mt-3 mb-2">Sichtbar während des Streams, unsichtbar danach.</div>
    <div style="color: #737373;">Streaming ja, soziale Teilnahme nein. Der Browser schliesst, der Moment ist weg.</div>
  </div>
</div>

<div class="text-lg mt-10" style="color: #737373;">
Der Bruch passiert immer direkt nach dem Talk.
</div>

---
layout: center
class: text-center
---

# Die Erkenntnis

<div class="text-3xl mt-8 max-w-2xl mx-auto font-light leading-relaxed" style="color: #262626;">
  <span style="color: #4F46E5; font-weight: 700;">Flurgespräche</span> sind der wertvollste Teil jeder Konferenz.
</div>

<div class="text-xl mt-8 max-w-xl mx-auto" style="color: #737373;">
Was fehlt, ist nicht noch mehr Content. Es fehlt ein sozialer Layer.
</div>

<div class="mt-10 p-4 rounded-xl max-w-2xl mx-auto" style="background: #EEF2FF;">
  <div class="font-semibold text-sm" style="color: #4F46E5;">Der Produkt-Moment</div>
  <div class="text-sm mt-2 leading-relaxed" style="color: #525252;">
    Direkt nach einer Session ist der gemeinsame Kontext am stärksten. Genau dort muss Hybrid von Information zu Verbindung wechseln.
  </div>
</div>

---
layout: center
class: text-center
---

# Die Idee

<div class="text-xl mt-4 max-w-2xl mx-auto" style="color: #737373;">
Drei Schichten, eine Plattform — kein neues Event-Tool, sondern die fehlende Verbindung dazwischen.
</div>

<div class="mt-10 max-w-2xl mx-auto text-left">
  <div class="p-5 rounded-t-xl" style="background: #4F46E5; color: white;">
    <div class="text-xs font-semibold tracking-[0.16em] opacity-70">INTERACTION LAYER</div>
    <div class="font-semibold mt-1">Ping, Chat, 3-Minuten-Videocall</div>
  </div>
  <div class="p-5" style="background: #6366F1; color: white;">
    <div class="text-xs font-semibold tracking-[0.16em] opacity-70">CONNECTION ENGINE</div>
    <div class="font-semibold mt-1">Wer sollte wen treffen — genau jetzt</div>
  </div>
  <div class="p-5 rounded-b-xl" style="background: #818CF8; color: white;">
    <div class="text-xs font-semibold tracking-[0.16em] opacity-70">PRESENCE LAYER</div>
    <div class="font-semibold mt-1">Wer ist hier, wo, und was macht diese Person gerade</div>
  </div>
</div>

<div class="mt-8 text-sm max-w-xl mx-auto" style="color: #A3A3A3;">
Wir sind kein Streaming-Tool, kein Chat, kein CRM. Wir sind die Schicht, die aus Anwesenheit Begegnung macht.
</div>

---

# Der Flow

<div class="text-lg mb-6" style="color: #737373;">Vom Link zum Gespräch in unter 3 Minuten.</div>

<div class="grid grid-cols-4 gap-4">
  <div class="text-center">
    <img src="./paper-presence-feed.jpg" alt="Presence Feed" style="width: 155px; margin: 0 auto; border-radius: 24px; border: 1px solid #E5E5E5; box-shadow: 0 10px 24px rgba(0,0,0,0.08);" />
    <div class="text-xs font-semibold mt-3" style="color: #4F46E5;">1. PRESENCE FEED</div>
    <div class="text-sm mt-1" style="color: #737373;">Wer ist jetzt relevant?</div>
  </div>
  <div class="text-center">
    <img src="./paper-session-detail.jpg" alt="Session Detail" style="width: 155px; margin: 0 auto; border-radius: 24px; border: 1px solid #E5E5E5; box-shadow: 0 10px 24px rgba(0,0,0,0.08);" />
    <div class="text-xs font-semibold mt-3" style="color: #4F46E5;">2. SESSION KONTEXT</div>
    <div class="text-sm mt-1" style="color: #737373;">Gleicher Talk, gleicher Moment</div>
  </div>
  <div class="text-center">
    <img src="./paper-mutual-match.jpg" alt="Mutual Match" style="width: 155px; margin: 0 auto; border-radius: 24px; box-shadow: 0 14px 28px rgba(79,70,229,0.20);" />
    <div class="text-xs font-semibold mt-3" style="color: #4F46E5;">3. IT'S A MATCH</div>
    <div class="text-sm mt-1" style="color: #737373;">Beidseitiges Interesse</div>
  </div>
  <div class="text-center">
    <img src="./paper-video-call.jpg" alt="Video Call" style="width: 155px; margin: 0 auto; border-radius: 24px; box-shadow: 0 14px 28px rgba(0,0,0,0.18);" />
    <div class="text-xs font-semibold mt-3" style="color: #4F46E5;">4. 3-MIN CALL</div>
    <div class="text-sm mt-1" style="color: #737373;">Sofort ins Gespräch</div>
  </div>
</div>

<div class="mt-8 text-center text-xl font-light" style="color: #262626;">
  Vom <span style="color: #737373;">Passivsein</span> zum
  <span style="color: #4F46E5; font-weight: 700;">Verbinden</span>
</div>

---

# Was heute funktioniert

<div class="text-lg " style="color: #737373;">Gebaut und (teilweise) lauffähig — kein Mockup.</div>

<div class="grid grid-cols-2 gap-6">
  <div class="space-y-3">
    <div class="p-4 rounded-xl" style="background: #F0FDF4; border: 1px solid #BBF7D0;">
      <div class="font-semibold text-sm" style="color: #166534;">Magic-Link-Auth & Onboarding</div>
      <div class="text-sm mt-1" style="color: #525252;">Kein Passwort. Link klicken, Typ wählen, 3 Tags, drin. Unter 60 Sekunden.</div>
    </div>
    <div class="p-4 rounded-xl" style="background: #F0FDF4; border: 1px solid #BBF7D0;">
      <div class="font-semibold text-sm" style="color: #166534;">Ping → Match → Chat</div>
      <div class="text-sm mt-1" style="color: #525252;">Echtzeit-Updates via WebSockets. Filtert nach Typ, Status und Interessen.</div>
    </div>
    <div class="p-4 rounded-xl" style="background: #F0FDF4; border: 1px solid #BBF7D0;">
      <div class="font-semibold text-sm" style="color: #166534;">Ping → Match → Chat</div>
      <div class="text-sm mt-1" style="color: #525252;">Ein Tap. Gegenseitiges Interesse. Sofort im Gespräch. Echtzeit via Broadcasting.</div>
    </div>
    <div class="p-4 pb-2 rounded-xl" style="background: #F0FDF4; border: 1px solid #BBF7D0;">
      <div class="font-semibold text-sm" style="color: #166534;">Sessions mit Check-in & Reaktionen</div>
      <div class="text-sm mt-1" style="color: #525252;">QR-Check-in, Live-Reaktionen, Q&A mit Voting, Post-Session-Connections.</div>
    </div>
  </div>
  <div class="space-y-3">
    <div class="p-4 rounded-xl" style="background: #E2D57D89; border: 1px solid #FFD301;">
      <div class="font-semibold text-sm" style="color: #166534;">Discovery Engine</div>
      <div class="text-sm mt-1" style="color: #525252;">Matching-Algorithmus mit Scoring, gewichteten Interessen und Session-Kontext.</div>
    </div>
    <div class="p-4 rounded-xl" style="background: #E2D57D89; border: 1px solid #FFD301;">
      <div class="font-semibold text-sm" style="color: #166534;">Booths mit Lead-Capture</div>
      <div class="text-sm mt-1" style="color: #525252;">Virtuelle Booths, Besucherhistorie, Staff-Tools. Physical und Remote gleich.</div>
    </div>
    <div class="p-4 rounded-xl" style="background: #F0FDF4; border: 1px solid #BBF7D0;">
      <div class="font-semibold text-sm" style="color: #166534;">Organizer-Dashboard</div>
      <div class="text-sm mt-1" style="color: #525252;">Live-KPIs, Teilnehmermanagement.</div>
    </div>
    <div class="p-4 rounded-xl" style="background: #E2D57D89; border: 1px solid #FFD301;">
      <div class="font-semibold text-sm" style="color: #166534;">Videocall-Signaling</div>
      <div class="text-sm mt-1" style="color: #525252;">WebRTC-Raum-Generierung, Server-Side-Signaling. Peer-to-Peer ready.</div>
    </div>
  </div>
</div>

<div class="mt-4 text-sm text-center" style="color: #A3A3A3;">
20+ Models · 30+ Controllers · Full Test Suite · Komplett auf Deutsch lokalisiert
</div>

---

# Was noch fehlt

<div class="text-lg mb-6" style="color: #737373;">Ehrlich: das ist ein Hackathon-MVP. Hier sind die Lücken.</div>

<div class="grid grid-cols-3 gap-5 mt-6">
  <div class="p-4 rounded-xl" style="background: #FEF3C7; border: 1px solid #FDE68A;">
    <div class="font-semibold text-sm" style="color: #B45309;">PWA-Shell</div>
    <div class="text-sm mt-2" style="color: #92400E;">Manifest und Service Worker existieren, aber Offline-Support und Push-Notifications sind noch nicht produktionsreif.</div>
  </div>
  <div class="p-4 rounded-xl" style="background: #FEF3C7; border: 1px solid #FDE68A;">
    <div class="font-semibold text-sm" style="color: #B45309;">Video-Calls (WebRTC)</div>
    <div class="text-sm mt-2" style="color: #92400E;">Signaling steht. Peer-to-Peer-Verbindung funktioniert lokal, braucht aber TURN-Server für Produktion.</div>
  </div>
  <div class="p-4 rounded-xl" style="background: #FEF3C7; border: 1px solid #FDE68A;">
    <div class="font-semibold text-sm" style="color: #B45309;">Smart Notifications</div>
    <div class="text-sm mt-2" style="color: #92400E;">In-App-Notifications funktionieren. Push, Batching und Frequency Limits fehlen noch.</div>
  </div>
  <div class="p-4 rounded-xl" style="background: #FEF3C7; border: 1px solid #FDE68A;">
    <div class="font-semibold text-sm" style="color: #B45309;">Serendipity Mode</div>
    <div class="text-sm mt-2" style="color: #92400E;">Der Matching-Algorithmus priorisiert Relevanz. Die bewusste Cross-Discipline-Logik ist konzipiert, aber nicht implementiert.</div>
  </div>
  <div class="p-4 rounded-xl" style="background: #FEF3C7; border: 1px solid #FDE68A;">
    <div class="font-semibold text-sm" style="color: #B45309;">Post-Event Export</div>
    <div class="text-sm mt-2" style="color: #92400E;">Connections werden gespeichert. Export als vCard oder CSV und Follow-up-Nudges fehlen.</div>
  </div>
  <div class="p-4 rounded-xl" style="background: #FEF3C7; border: 1px solid #FDE68A;">
    <div class="font-semibold text-sm" style="color: #B45309;">Skalierung</div>
    <div class="text-sm mt-2" style="color: #92400E;">Getestet mit Seed-Daten. Nicht lastgetestet für 500+ gleichzeitige WebSocket-Verbindungen.</div>
  </div>
</div>

---

# Wohin das führt

<div class="text-lg mb-8" style="color: #737373;">Vom Hackathon-Prototyp zur Plattform.</div>

<div class="grid grid-cols-3 gap-8 mt-4">
  <div>
    <div class="text-xs font-semibold tracking-[0.16em] mb-4" style="color: #4F46E5;">PHASE 1 — PILOT</div>
    <div class="space-y-3 text-sm" style="color: #525252;">
      <div class="p-3 rounded-lg" style="background: #F5F5F5;">Ein reales Event, 50–200 Teilnehmende</div>
      <div class="p-3 rounded-lg" style="background: #F5F5F5;">PWA produktionsreif machen</div>
      <div class="p-3 rounded-lg" style="background: #F5F5F5;">Push Notifications</div>
      <div class="p-3 rounded-lg" style="background: #F5F5F5;">Video-Calls stabilisieren</div>
    </div>
  </div>
  <div>
    <div class="text-xs font-semibold tracking-[0.16em] mb-4" style="color: #4F46E5;">PHASE 2 — VALIDIERUNG</div>
    <div class="space-y-3 text-sm" style="color: #525252;">
      <div class="p-3 rounded-lg" style="background: #F5F5F5;">Cross-Pollination messen</div>
      <div class="p-3 rounded-lg" style="background: #F5F5F5;">Serendipity Mode aktivieren</div>
      <div class="p-3 rounded-lg" style="background: #F5F5F5;">Sponsor-ROI Dashboard</div>
      <div class="p-3 rounded-lg" style="background: #F5F5F5;">Multi-Event Support</div>
    </div>
  </div>
  <div>
    <div class="text-xs font-semibold tracking-[0.16em] mb-4" style="color: #4F46E5;">PHASE 3 — PLATTFORM</div>
    <div class="space-y-3 text-sm" style="color: #525252;">
      <div class="p-3 rounded-lg" style="background: #F5F5F5;">API für Event-Tool-Integration</div>
      <div class="p-3 rounded-lg" style="background: #F5F5F5;">AI Conversation Starters</div>
      <div class="p-3 rounded-lg" style="background: #F5F5F5;">Interaction Graph Analytics</div>
      <div class="p-3 rounded-lg" style="background: #F5F5F5;">Cross-Event Identity</div>
    </div>
  </div>
</div>

<div class="mt-2 p-4 rounded-xl text-center max-w-2xl mx-auto" style="background: #EEF2FF;">
  <div class="text-lg" style="color: #4F46E5; font-weight: 600;">
    Die Kernfrage: Entsteht durch diesen Layer tatsächlich Verbindung, die sonst nicht passiert wäre?
  </div>
  <div class="text-sm mt-2" style="color: #525252;">Das lässt sich nur mit einem echten Pilot beantworten.</div>
</div>

---
layout: center
class: text-center
---

# Wie das gebaut wurde

<div class="text-xl mt-6 max-w-xl mx-auto" style="color: #737373;">
Ein Entwickler. KI-gestützt. Drei Wochen.
</div>

<div class="mt-10 grid grid-cols-4 gap-6 max-w-3xl mx-auto">
  <div class="text-center">
    <div class="text-3xl font-bold" style="color: #4F46E5;">20+</div>
    <div class="text-sm mt-1" style="color: #737373;">Models</div>
  </div>
  <div class="text-center">
    <div class="text-3xl font-bold" style="color: #4F46E5;">30+</div>
    <div class="text-sm mt-1" style="color: #737373;">Controllers</div>
  </div>
  <div class="text-center">
    <div class="text-3xl font-bold" style="color: #4F46E5;">20+</div>
    <div class="text-sm mt-1" style="color: #737373;">Vue Pages</div>
  </div>
  <div class="text-center">
    <div class="text-3xl font-bold" style="color: #4F46E5;">RT</div>
    <div class="text-sm mt-1" style="color: #737373;">WebSockets</div>
  </div>
</div>

<div class="mt-10 text-sm max-w-lg mx-auto" style="color: #A3A3A3;">
Laravel 13 · Inertia v3 · Vue 3 · Tailwind v4 · Reverb · Pest · PWA
</div>

---
layout: center
class: text-center
---

# Danke

<div class="mt-8 text-lg" style="color: #525252;">
Fragen? Demo? Zusammenarbeit?
</div>

<div class="mt-8 space-y-2">
  <div style="color: #737373;">riccardo.previti@uoiea.ch</div>
  <div style="color: #737373;">github.com/Verickt/hybridpresencelayer</div>
</div>

<div class="mt-12">
  <span class="px-4 py-2 rounded-lg text-white font-semibold" style="background: #4F46E5;">
    Hybrid Presence Layer
  </span>
</div>

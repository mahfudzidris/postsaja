# PostSaja — Feature Requests

> Auto-logged from customer calls & discovery

## 2026-06-13 — Tokey Kedai Makan

**Source:** Phone call

### 1. Multi-login / Staff Management (Priority: 🔴 High)

Tokey nak ada multiple login untuk staff dengan role berbeza.

| Role | Access |
|------|--------|
| **Staff** | Upload gambar via WhatsApp sahaja — tak perlu app, tak boleh ubah setting |
| **Supervisor** | Approve content, view stats |
| **Owner** | Full access — billing, analytics, branding, team management |

**Key requirement:** Staff guna WhatsApp macam biasa. Hantar gambar → AI urus sisanya.

### 2. WhatsApp Bot Upload (Priority: 🔴 High)

**Workflow:**
```
Staff snap gambar kerja
    ↓ (hantar ke WA PostSaja)
AI generate caption + target platform
    ↓
Auto post ke Google Business + WhatsApp Status + etc
    ↓
Owner dapat daily summary (WA/email)
```

**"Aku taknak ajar staff guna app baru — WA je dah cukup."**

---

## 2026-06-14 — Competitive Intel

### Post-Bridge (post-bridge.com)

**Type:** Social media scheduling tool
**Users:** 1,500+ creators & teams
**Platforms:** IG, TikTok, YT, X, LinkedIn, FB, Pinterest, Threads, Bluesky
**Key features:**
- Cross-posting scheduler
- AI agent mode (Claude/Cursor plugin — post via text command)
- Story posts support
- API access ($5/month add-on)

**PostSaja edge:**
- ❌ Tak support Google Business Profile
- ❌ Tak support WhatsApp
- ❌ Tak ada AI generate caption from photo (manual schedule je)
- ❌ English only — target creators US/EU
- ❌ Tak ada auto-retention (repeat customer reminders)
- ❌ Mahal ($$$)

### TapTapJe (taptapje.com)

**Type:** Unknown (Webflow site, kemungkinan auto-posting tool)
**Note:** Competitor paling dekat dengan PostSaja dari segi domain & konsep. Perlu monitor rapat.

### Market Insight

> Competition confirms market ni panas — tapi semua general-purpose scheduler. Takde satu pun fokus pada problem SME Malaysia: staff upload photo → AI auto-post → customer datang balik.

**PostSaja positioning:**
Bukan scheduler. PostSaja adalah **AI marketing assistant untuk orang yang tak sempat fikir content.**

---

### Product Implications

- PostSaja perlu jadi dua-layer: **Staff Interface (WA)** + **Boss Dashboard (Web/Mobile)**
- MVP boleh start dengan: Boss add staff → staff dapat link/QR → submit via web form simple
- Phase 2: WA bot untuk staff upload

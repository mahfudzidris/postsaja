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

### Product Implications

- PostSaja perlu jadi dua-layer: **Staff Interface (WA)** + **Boss Dashboard (Web/Mobile)**
- MVP boleh start dengan: Boss add staff → staff dapat link/QR → submit via web form simple
- Phase 2: WA bot untuk staff upload

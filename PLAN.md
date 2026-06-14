# PostSaja — Development Plan

> Status: **Phase 0 ✅** — Laravel Cloud backend live, Telegram bot working

---

## ✅ Phase 0: Foundation (SELESAI)

| Item | Status |
|------|--------|
| Laravel Cloud backend | ✅ deployed at `postsaja-backend-main-60lgtj.laravel.cloud` |
| GitHub repo | ✅ `github.com/mahfudzidris/postsaja-backend` |
| Telegram bot webhook | ✅ /start, business code, photo processing |
| Database (MySQL) | ✅ migrations: businesses, staff_telegram, posts |
| Demo data | ✅ codes BENGKEL & MAKAN |

---

## 🎯 Phase 1: Core Staff Flow (Telegram)

### 1.1 True AI Caption
**Replace mock AI with real AI API**

- [ ] Integrate Claude / GPT API via Laravel
- [ ] Analyze photo content → generate context-aware caption
- [ ] Auto-generate hashtags (localised: #SME #Malaysia #servis)
- [ ] Support multilingual (BM/EN mix)
- [ ] Store AI conversation log for improvement

**File:** `app/Services/AICaptionService.php`

### 1.2 Multi-Business Support
**Staff can belong to multiple businesses**

- [ ] Remove single-business constraint
- [ ] Add /businesses command to list & switch
- [ ] Add /join <code> to join additional businesses
- [ ] Default business selection per staff

### 1.3 Post Review Queue
**Supervisor approves before posting**

- [ ] New table: `postsaja_pending_posts`
- [ ] Telegram buttons: ✅ Approve / ❌ Reject
- [ ] Supervisor notification when new post pending
- [ ] 24h auto-expiry for unapproved posts

### 1.4 Post History & Stats
- [ ] `/stats` command — weekly/monthly counts
- [ ] `/history` — last 10 posts
- [ ] `/status <post_id>` — check delivery status

---

## 🖥️ Phase 2: Owner Dashboard

### 2.1 Web Dashboard (Laravel + React/Inertia)
- [ ] Login via Telegram / email
- [ ] Owner dashboard: posts, staff, analytics
- [ ] CRUD businesses
- [ ] Invite staff (generate business code)
- [ ] View all posts with AI captions
- [ ] Approve/reject from web
- [ ] Connect social accounts (Google Business, FB, IG)

### 2.2 Mobile-Responsive
- [ ] PWA support
- [ ] Push notifications for new posts

### 2.3 Owner Daily Summary
- [ ] Auto-generated summary every 6pm
- [ ] Sent via Telegram
- [ ] Metrics: posts today, views, likes, new customers
- [ ] Weekly PDF report

---

## 📤 Phase 3: Multi-Platform Posting

### 3.1 Platform Integrations
| Platform | API | Priority |
|----------|-----|----------|
| Google Business Profile | Google My Business API | 🔴 High |
| WhatsApp Status | WA Business API / 360Dialog | 🔴 High |
| Facebook Page | Graph API | 🟡 Medium |
| Instagram Business | Graph API | 🟡 Medium |
| TikTok Business | TikTok Business API | 🟢 Low |

### 3.2 Scheduled Posting
- [ ] Queue system (Laravel Jobs + Queue)
- [ ] Best-time-to-post algorithm
- [ ] Retry on failure with backoff
- [ ] Platform-specific image resizing

---

## 💬 Phase 4: WhatsApp Staff Interface

### 4.1 WhatsApp Bot (Staff Upload)
- [ ] 360Dialog / WATI / Twilio integration
- [ ] QR-based staff registration
- [ ] Forward WhatsApp images → AI → same pipeline
- [ ] WhatsApp reply with post confirmation

### 4.2 WhatsApp Scheduling
- [ ] Staff can set post time
- [ ] Multiple images in one post
- [ ] Location tag from WhatsApp

---

## 📊 Phase 5: Retention & Growth

### 5.1 Customer Retention Loop
- [ ] Auto-reminder: "Jangan lupa update gambar hari ni 📸"
- [ ] Engagement analytics
- [ ] "Best post" weekly highlight

### 5.2 Billing
- [ ] Subscription tiers (Staff count, posts/month)
- [ ] Stripe / FPX integration
- [ ] Free trial (14 days)

### 5.3 Referral
- [ ] Referral codes
- [ ] "Bawak kawan dapat free 1 bulan"

---

## 🏗️ Architecture

```
Telegram ──→ Laravel Webhook ──→ AICaptionService ──→ Queue ──→ Platform API
                    │                                        │
                    ▼                                        ▼
              Database (MySQL)                     Google / FB / IG / WA
                    │
                    ▼
         Owner Dashboard (Web)
```

**Current file structure:**
```
app/
├── Http/Controllers/TelegramWebhookController.php
├── Models/
│   ├── PostsajaBusiness.php
│   ├── PostsajaStaffTelegram.php
│   └── PostsajaPost.php
├── Services/
│   └── (to be created)
database/
├── migrations/  ✅ all done
└── seeders/
    └── PostsajaDemoSeeder.php
```

---

## Priority Recommendations (Next 2 Weeks)

| Priority | Task | Effort | Impact |
|----------|------|--------|--------|
| 🥇 | True AI caption (Claude) | 2-3h | Core value prop |
| 🥇 | Owner daily summary via Telegram | 4h | Engagement driver |
| 🥈 | Google Business API posting | 8-12h | Biggest customer win |
| 🥈 | Post review queue | 4h | Supervisor workflow |
| 🥉 | WhatsApp bot (Phase 4) | 16h+ | Key differentiator |

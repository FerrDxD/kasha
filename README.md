<div align="center">

# 🌊 KASHA

### Modern Personal Finance Operating System

**Account-first • Rule-based • Insight-driven**

[![Vue.js](https://img.shields.io/badge/Vue-3.5-42b883?style=flat&logo=vue.js&logoColor=white)](https://vuejs.org/)
[![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20?style=flat&logo=laravel&logoColor=white)](https://laravel.com/)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind-4.1-38bdf8?style=flat&logo=tailwind-css&logoColor=white)](https://tailwindcss.com/)
[![License](https://img.shields.io/badge/License-MIT-green?style=flat)](LICENSE)

[![Issues](https://img.shields.io/github/issues/FerrDxD/kasha?style=flat)](https://github.com/FerrDxD/kasha/issues)
[![Pull Requests](https://img.shields.io/github/issues-pr/FerrDxD/kasha?style=flat)](https://github.com/FerrDxD/kasha/pulls)
[![Stars](https://img.shields.io/github/stars/FerrDxD/kasha?style=flat)](https://github.com/FerrDxD/kasha)

---

*KASHA is not an expense tracker. It's a complete personal finance operating system built for the modern era.*

</div>

---

## ✨ Overview

KASHA is a sophisticated personal finance application that goes beyond simple expense tracking. It's a comprehensive **financial operating system** where money lives in accounts, moves through transactions, is constrained by budgets, directed toward goals, and analyzed through cash flow projections and monthly reports.

### Core Philosophy

- **Account-First**: Every transaction belongs to an account. Balances are always reconcilable.
- **Automation Over Manual**: Rules + recurring transactions + CSV import reduce manual input.
- **Insight, Not Just Data**: Dashboard answers "Is this month safe?" at a glance.
- **Precision**: Money = integer. All amounts stored as `bigint` (Rupiah).

### Non-Goals (v1)

- ❌ Bank connection / open banking
- ❌ Multi-user / household sharing
- ❌ Investment / stock portfolio
- ❌ Multi-currency (schema prepared, not used in v1)
- ❌ Native mobile apps

---

## 🏗️ Architecture

```
┌─────────────────────────────────────────────────────────┐
│                   Browser Layer                          │
│  Vue 3 + Inertia.js + Tailwind CSS + shadcn/ui          │
└─────────────────────────────────────────────────────────┘
                          │
                    Inertia Visits
                          │
                          ▼
┌─────────────────────────────────────────────────────────┐
│              Application Layer (Laravel 11)             │
│  • Domain-Driven Design (Actions, Queries, Data)         │
│  • Policies + Global Scopes for data isolation           │
│  • Vercel Cron for scheduled tasks                       │
└─────────────────────────────────────────────────────────┘
                          │
        ┌─────────────────┼─────────────────┐
        │                 │                 │
        ▼                 ▼                 ▼
┌──────────────┐  ┌──────────────┐  ┌──────────────┐
│   Supabase   │  │  Supabase   │  │   Database   │
│   Postgres   │  │   Storage   │  │   Cache      │
│  (Pooler)    │  │  (CSV/PDF)  │  │  (Sessions)  │
└──────────────┘  └──────────────┘  └──────────────┘
```

### Tech Stack

| Layer | Technology | Purpose |
|-------|-----------|---------|
| **Frontend** | Vue 3 | Reactive UI framework |
| **Routing** | Inertia.js | SPA-like experience without API |
| **Styling** | Tailwind CSS 4.1 | Utility-first CSS |
| **Components** | shadcn/ui | Accessible component library |
| **Backend** | Laravel 11 | PHP framework |
| **Database** | Supabase Postgres | Managed PostgreSQL |
| **Storage** | Supabase Storage | File storage (CSV, PDF) |
| **Hosting** | Vercel | Serverless deployment |
| **Icons** | Lucide Vue | Icon library |

---

## 🚀 Quick Start

### Prerequisites

- PHP 8.2+
- Node.js 20+
- Composer
- Supabase account (free tier sufficient)

### Installation

```bash
# Clone the repository
git clone https://github.com/FerrDxD/kasha.git
cd kasha

# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure Supabase in .env
# DB_CONNECTION=pgsql
# DB_HOST=your-project.supabase.co
# DB_PORT=6543 (pooler) or 5432 (direct)
# DB_DATABASE=postgres
# DB_USERNAME=postgres
# DB_PASSWORD=your-password

# Run migrations
php artisan migrate

# Start development server
npm run dev
```

### Build for Production

```bash
# Build assets
npm run build

# Optimize application
php artisan optimize
```

---

## 📚 Features

### ✅ Core Features (v1)

| Feature | Priority | Description |
|---------|----------|-------------|
| **Accounts** | P0 | Cash, bank, e-wallet, credit cards, savings. Opening balance, archiving |
| **Categories** | P0 | 2-level hierarchy (parent/child), income/expense types, icons & colors |
| **Transactions** | P0 | Income, expense, **transfer** between accounts, tags, notes, filter/search |
| **Budgeting** | P0 | Monthly budgets per category, progress bars, optional rollover |
| **Dashboard** | P0 | Balance, income vs expense, budget progress, goals, upcoming bills |
| **Recurring** | P1 | Salary, bills, subscriptions. Auto-generate or reminder |
| **Transaction Rules** | P1 | Auto-categorize/tag/account based on conditions |
| **CSV Import/Export** | P1 | Column mapping, preview, duplicate detection, filtered export |
| **Financial Goals** | P1 | Target amount + date, contributions, projections |
| **Cash Flow** | P1 | Historical + 30/60/90 day projections (from recurring) |
| **Monthly Report** | P2 | Monthly summary, top categories, vs last month, PDF export |

### 🎨 Design System

KASHA follows a **Modern Dark Financial Minimalist** aesthetic:

- **Color Palette**: Deep oceanic slates with vivid semantic indicators
  - Background: `#0b1326` (deep navy)
  - Primary accent: `#4edea3` (emerald - financial growth)
  - Income: `#34d399` (emerald-400)
  - Expense: `#f43f5e` (rose-500)
  - Warning: `#fbbf24` (amber-400)
- **Typography**: Inter for UI, tabular numerics for financial data
- **Rounded corners**: 8px (default), 16px (cards)
- **Icons**: Lucide outlined icons (24px, 1.75px stroke)

See [`design-reference/design.md`](design-reference/design.md) for complete design tokens.

---

## 📁 Project Structure

```
kasha/
├── app/
│   ├── Domain/              # Domain-driven design layer
│   │   ├── Accounts/
│   │   ├── Transactions/
│   │   ├── Budgets/
│   │   ├── Recurring/
│   │   ├── Rules/
│   │   ├── Goals/
│   │   └── Reports/
│   ├── Http/Controllers/    # Thin controllers (Actions → Inertia)
│   └── Models/              # Eloquent models with global scopes
├── resources/
│   ├── css/
│   │   └── app.css         # Tailwind imports + custom styles
│   └── js/
│       ├── components/      # Vue components
│       │   ├── ui/         # shadcn/ui components
│       │   └── ...         # Custom components
│       ├── composables/     # Vue composables
│       ├── pages/          # Inertia pages
│       └── layouts/        # Layout components
├── design-reference/        # Visual design system
│   ├── design.md          # Global design tokens
│   └── [screen]/          # Screen-specific references
├── database/
│   └── migrations/         # Database migrations
└── tests/                 # Feature tests (Pest)
```

---

## 🔐 Security

- **Data Isolation**: All queries scoped to `user_id` via global scopes
- **Policies**: Authorization checks on every resource
- **Rate Limiting**: Login and import endpoints
- **Validation**: File size/MIME validation for CSV uploads
- **Signed Cron**: Scheduled tasks via signed headers
- **No Secrets**: Never commit `.env` or sensitive data

See [SECURITY.md](SECURITY.md) for details.

---

## 🧪 Testing

```bash
# Run feature tests
vendor/bin/pest

# Run with coverage
vendor/bin/pest --coverage

# Run specific test
vendor/bin/pest --filter AccountsTest
```

---

## 🤝 Contributing

We welcome contributions! Please read our [Contributing Guidelines](CONTRIBUTING.md) before submitting PRs.

### Development Workflow

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Code Style

- **PHP**: Follow PSR-12, use Laravel Pint
- **TypeScript**: Follow Vue 3 style guide
- **CSS**: Use Tailwind utilities over custom CSS
- **Design**: Follow [`design-reference/design.md`](design-reference/design.md)

---

## 📜 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## 🙏 Acknowledgments

- **Laravel** for the elegant PHP framework
- **Vue.js** for the reactive frontend
- **Supabase** for the managed database
- **Vercel** for the serverless platform
- **shadcn/ui** for the beautiful components
- **Tailwind CSS** for the utility-first styling

---

## 📞 Support

- 📖 [Documentation](docs/)
- 🚀 [Deployment Guide](DEPLOYMENT.md)
- 🐛 [Issue Tracker](https://github.com/FerrDxD/kasha/issues)
- 💬 [Discussions](https://github.com/FerrDxD/kasha/discussions)

---

<div align="center">

**Built with ❤️ for personal financial freedom**

[⬆ Back to Top](#-kasha)

</div>

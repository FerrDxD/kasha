<div align="center">

# 🚀 Contributing to KASHA

**Thank you for considering contributing to KASHA!**

We welcome contributions from everyone, whether you're fixing a bug, adding a feature, improving documentation, or reporting an issue.

</div>

---

## 📋 Table of Contents

- [Getting Started](#getting-started)
- [Development Setup](#development-setup)
- [Coding Standards](#coding-standards)
- [Design Guidelines](#design-guidelines)
- [Testing](#testing)
- [Pull Request Process](#pull-request-process)
- [Documentation](#documentation)
- [Development Workflow](#development-workflow)

---

## 🎯 Getting Started

### Ways to Contribute

You can contribute to KASHA in many ways:

- 🐛 **Report bugs** - Found an issue? Let us know!
- 💡 **Suggest features** - Have an idea? Share it!
- 📝 **Improve documentation** - Help make docs clearer
- 🔧 **Fix bugs** - Submit a PR with a fix
- ✨ **Add features** - Implement new functionality
- 🎨 **Improve design** - Enhance UI/UX
- 🧪 **Add tests** - Improve test coverage
- 🌍 **Translate** - Help with internationalization

### Before You Start

1. **Read the documentation** - Familiarize yourself with the project
2. **Check existing issues** - Avoid duplicating work
3. **Discuss major changes** - Open an issue first for big features
4. **Start small** - Pick a good first issue if you're new

---

## 🛠️ Development Setup

### Prerequisites

- PHP 8.2 or higher
- Node.js 20 or higher
- Composer
- Git
- Supabase account (free tier)

### Step-by-Step Setup

```bash
# 1. Fork and clone the repository
git clone https://github.com/YOUR_USERNAME/kasha.git
cd kasha

# 2. Add upstream remote
git remote add upstream https://github.com/FerrDxD/kasha.git

# 3. Install PHP dependencies
composer install

# 4. Install Node dependencies
npm install

# 5. Copy environment file
cp .env.example .env

# 6. Generate application key
php artisan key:generate

# 7. Configure Supabase in .env
# Edit .env with your Supabase credentials:
# DB_CONNECTION=pgsql
# DB_HOST=your-project.supabase.co
# DB_PORT=6543
# DB_DATABASE=postgres
# DB_USERNAME=postgres
# DB_PASSWORD=your-password

# 8. Run migrations
php artisan migrate

# 9. Seed database (optional)
php artisan db:seed

# 10. Start development server
npm run dev
```

### Development Commands

```bash
# PHP/Laravel
php artisan serve              # Start PHP server
php artisan migrate             # Run migrations
php artisan migrate:fresh       # Fresh migration
php artisan db:seed             # Seed database
php artisan tinker              # Laravel REPL
php artisan queue:work          # Process queue (if using)

# Node/Vue
npm run dev                     # Start Vite dev server
npm run build                   # Build for production
npm run types:check             # TypeScript type checking
npm run check                   # Run linting
npm run check:fix               # Fix linting issues

# Testing
vendor/bin/pest                  # Run PHP tests
vendor/bin/pest --coverage       # With coverage
```

---

## 📐 Coding Standards

### PHP (Laravel)

**Follow PSR-12 coding standard**

```php
// ✅ Good
class TransactionService
{
    public function create(array $data): Transaction
    {
        return Transaction::create($data);
    }
}

// ❌ Bad
class transactionService
{
    public function create($data)
    {
        return Transaction::create($data);
    }
}
```

**Use Laravel Pint for formatting**

```bash
# Format code
./vendor/bin/pint
```

**Key conventions:**

- Class names: `PascalCase`
- Method names: `camelCase`
- Constants: `UPPER_SNAKE_CASE`
- Variables: `camelCase`
- Use type hints where possible
- Return types on all methods
- Docblocks for complex logic

### TypeScript/Vue

**Follow Vue 3 Style Guide**

```typescript
// ✅ Good
<script setup lang="ts">
import { ref, computed } from 'vue';

const count = ref(0);
const doubled = computed(() => count.value * 2);
</script>

// ❌ Bad
<script setup>
import { ref } from 'vue';

const Count = ref(0);
</script>
```

**Key conventions:**

- Use `<script setup>` with TypeScript
- Composition API over Options API
- Use `defineProps` and `defineEmits`
- Prefer composables for reusable logic
- Use type interfaces for props

### CSS/Tailwind

**Utility-first approach**

```vue
<!-- ✅ Good -->
<div class="flex items-center gap-4 p-6 rounded-lg bg-slate-800">
  <!-- content -->
</div>

<!-- ❌ Bad -->
<div class="custom-card">
  <!-- content -->
</div>

<style>
.custom-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.5rem;
  border-radius: 0.5rem;
  background: #1e293b;
}
</style>
```

**When to use custom CSS:**

- Only for animations
- For complex pseudo-elements
- When utilities become too verbose
- Extract to CSS class if used 3+ times

---

## 🎨 Design Guidelines

### Follow the Design System

**Always reference `design-reference/design.md`**

Before implementing UI changes:

1. Read the global design system
2. Check for existing screen references
3. Match the visual hierarchy
4. Use specified colors and spacing
5. Follow component patterns

### Color Usage

```vue
<!-- ✅ Use design tokens -->
<div class="bg-background text-foreground border-border">
  <span class="text-primary">Primary action</span>
  <span class="text-income-color">+ Rp 1.000.000</span>
  <span class="text-expense-color">- Rp 500.000</span>
</div>

<!-- ❌ Don't hardcode colors -->
<div style="background: #0b1326; color: #dae2fd;">
  <!-- content -->
</div>
```

### Typography

```vue
<!-- ✅ Use utility classes -->
<h1 class="text-2xl font-bold">Headline</h1>
<p class="text-sm text-muted-foreground">Subtitle</p>
<p class="num-body font-variant-numeric tabular-nums">Rp 1.250.000</p>

<!-- ❌ Don't use inline styles -->
<h1 style="font-size: 1.5rem; font-weight: 700;">
  Headline
</h1>
```

### Components

**Use existing shadcn/ui components when possible**

```vue
<!-- ✅ Good -->
<Button variant="primary" size="lg">
  Submit
</Button>

<!-- ❌ Don't reinvent -->
<button class="my-custom-button">
  Submit
</button>
```

---

## 🧪 Testing

### PHP Tests (Pest)

```php
// tests/Feature/TransactionTest.php
use App\Domain\Transactions\Actions\CreateTransaction;

it('can create a transaction', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();

    $transaction = CreateTransaction::execute([
        'user_id' => $user->id,
        'account_id' => $account->id,
        'amount' => 100000,
        'type' => 'expense',
    ]);

    expect($transaction->amount)->toBe(100000);
});
```

**Testing guidelines:**

- Write tests for all Actions
- Test edge cases and error conditions
- Use factories for test data
- Keep tests isolated and fast
- Aim for 80%+ coverage on critical paths

### Vue Tests (Vitest)

```typescript
// tests/components/MoneyInput.spec.ts
import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import MoneyInput from '@/components/MoneyInput.vue';

describe('MoneyInput', () => {
  it('formats currency correctly', () => {
    const wrapper = mount(MoneyInput, {
      props: { modelValue: 1000000 },
    });
    expect(wrapper.find('input').element.value).toBe('1.000.000');
  });
});
```

---

## 📝 Pull Request Process

### Before Submitting

1. **Update documentation** - If your change affects docs
2. **Add tests** - Ensure test coverage
3. **Run linters** - Fix any issues
4. **Test locally** - Verify everything works
5. **Check design** - Match the design system

### PR Checklist

- [ ] Code follows project style guidelines
- [ ] Tests added/updated and passing
- [ ] Documentation updated
- [ ] Commit messages are clear
- [ ] No console errors or warnings
- [ ] Design matches design system
- [ ] Accessibility considered
- [ ] Responsive design tested

### Commit Message Format

Follow [Conventional Commits](https://www.conventionalcommits.org/):

```
feat: add dark mode toggle
fix: resolve transaction duplicate detection issue
docs: update contributing guidelines
style: format code with pint
refactor: simplify transaction service
test: add tests for account creation
chore: update dependencies
```

### PR Template

```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Related Issue
Fixes #123

## Changes Made
- List of changes

## Screenshots (if applicable)
[Add screenshots]

## Testing
- [ ] Manual testing
- [ ] Automated tests
- [ ] Browser testing

## Checklist
- [ ] Code follows style guidelines
- [ ] Tests added/updated
- [ ] Documentation updated
```

---

## 📚 Documentation

### Code Documentation

**PHPDoc for classes and methods**

```php
/**
 * Create a new transaction.
 *
 * @param array{user_id: string, account_id: string, amount: int, type: string} $data
 * @return Transaction
 * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
 */
public function create(array $data): Transaction
{
    // implementation
}
```

**JSDoc for TypeScript**

```typescript
/**
 * Format amount as Indonesian Rupiah
 * @param amount - Amount in integer (cents)
 * @returns Formatted string (e.g., "Rp 1.250.000")
 */
export function formatMoney(amount: number): string {
  // implementation
}
```

### Feature Documentation

When adding features:

1. Update README if it's user-facing
2. Add inline comments for complex logic
3. Update design-reference if UI changes
4. Consider adding a docs/ guide if complex

---

## 🔄 Development Workflow

### 1. Choose an Issue

- Check issues labeled `good first issue` if you're new
- Comment on the issue to claim it
- Ask questions if anything is unclear

### 2. Create a Branch

```bash
git checkout -b feature/your-feature-name
# or
git checkout -b fix/your-bug-fix
```

### 3. Make Changes

- Work on your feature
- Follow coding standards
- Add tests
- Update documentation

### 4. Commit Changes

```bash
git add .
git commit -m "feat: add your feature description"
```

### 5. Push and Test

```bash
git push origin feature/your-feature-name
```

### 6. Open Pull Request

- Go to GitHub
- Click "New Pull Request"
- Fill out the PR template
- Request review

### 7. Address Feedback

- Respond to review comments
- Make requested changes
- Push updates to your branch

### 8. Merge

- After approval, maintainers will merge
- Celebrate! 🎉

---

## 💡 Tips for Contributors

### First-Time Contributors

- Start with documentation improvements
- Fix small bugs labeled `good first issue`
- Ask questions in issues or discussions
- Join our community chat (if available)

### Experienced Contributors

- Help review PRs from newcomers
- Improve test coverage
- Refactor code for better maintainability
- Share knowledge in discussions

### Design Contributors

- Follow the design system strictly
- Create consistent UI patterns
- Test responsive behavior
- Consider accessibility

---

## 🎓 Learning Resources

### Laravel
- [Laravel Documentation](https://laravel.com/docs)
- [Laracasts](https://laracasts.com/)

### Vue.js
- [Vue 3 Documentation](https://vuejs.org/)
- [Vue Mastery](https://www.vuemastery.com/)

### Tailwind CSS
- [Tailwind Documentation](https://tailwindcss.com/docs)
- [Tailwind UI](https://tailwindui.com/)

### Testing
- [Pest Documentation](https://pestphp.com/docs)
- [Vitest Documentation](https://vitest.dev/)

---

## ❓ Getting Help

If you need help:

- **GitHub Issues** - Open an issue with the `question` label
- **GitHub Discussions** - Start a discussion
- **Community Chat** - Join our Discord/Slack (if available)
- **Email** - Reach out to maintainers

---

## 🙏 Recognition

We appreciate all contributions! Contributors will be:

- Listed in the contributors section
- Credited in release notes
- Thanked in our blog posts (if applicable)
- Invited to contributor events (if available)

---

<div align="center">

**Ready to contribute? Let's build something amazing together!**

[⬆ Back to Top](#-contributing-to-kasha)

</div>

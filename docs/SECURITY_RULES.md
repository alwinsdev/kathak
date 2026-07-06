# Enterprise Laravel Security Rules

**Version:** 3.0  
**Security Level:** Enterprise Governance Grade  
**Scope:** Architecture · Development · AppSec · Audit · Operations  
**Aligned With:** OWASP Cheat Sheet Series · Laravel Best Practices · FinTech/ERP Standards

---

## Core Security Philosophy

The system must assume:

- All input is hostile
- All users are untrusted until authorized
- All APIs are publicly reachable
- All financial operations are attack surfaces
- All state changes require traceability
- All concurrency creates corruption risk

> **Security is not a middleware. It is architecture, domain integrity, transaction safety, auditability, and operational resilience — baked into every layer.**

---

## Trust Boundary — Every Request Must Flow In This Order

```
Request
  ↓ Input Validation
  ↓ Authentication
  ↓ Authorization (Policy/Gate)
  ↓ Business Rule Validation
  ↓ DB Transaction (with row lock where needed)
  ↓ Persistence
  ↓ Audit Log
  ↓ Response
```

Never skip or reorder layers.

---

## Rule 1 — Application Basics

### 1.1 Debug Mode

```env
# .env (production) — NEVER true in production
APP_DEBUG=false
APP_ENV=production
```

- `APP_DEBUG=true` exposes stack traces, SQL queries, environment variables, and internal paths to attackers.
- Always read from env — never hardcode `true` in `config/app.php`.

### 1.2 Application Key

```bash
php artisan key:generate
```

- Required for cookie encryption, signed URLs, password reset tokens, and session encryption.
- Never commit `APP_KEY` to version control.
- Rotate immediately if compromised (invalidates all existing sessions/cookies).

### 1.3 File & Directory Permissions

| Target                            | Max Permission | Notes                                  |
| --------------------------------- | -------------- | -------------------------------------- |
| All Laravel directories           | `775`          | Owner/group write; others read+execute |
| Non-executable files              | `664`          | Owner/group write; others read only    |
| Artisan / deploy scripts          | `775`          | Executable by owner and group only     |
| `storage/` and `bootstrap/cache/` | `775`          | Must be writable by web server user    |
| `.env`                            | `640`          | Never world-readable                   |

### 1.4 PHP Configuration

- Refer to [OWASP PHP Configuration Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/PHP_Configuration_Cheat_Sheet.html).
- Disable `display_errors` in production.
- Set `expose_php = Off`.

### 1.5 Dependency Security

```bash
composer audit        # Check for vulnerable PHP packages
npm audit             # Check for vulnerable JS packages
```

- Run on every CI/CD build.
- Monitor CVEs via Snyk, Dependabot, or similar.
- Remove unused packages, dead code, and unused endpoints immediately.
- Never use abandoned or unmaintained packages in production.

---

## Rule 2 — Cookie Security & Session Management

All session/cookie config lives in `config/session.php`. Default Laravel settings are secure — deviations require documented justification.

```php
// config/session.php — required production values
'http_only'  => true,          // Block JS access — prevents XSS cookie theft
'same_site'  => 'lax',         // Restrict to first-party context — CSRF mitigation
'secure'     => null,          // null = auto HTTPS; true = force HTTPS always
'domain'     => null,          // Prevent subdomain leakage
'lifetime'   => 15,            // 15 min for financial apps (OWASP: 2–5 min high-value)
'encrypt'    => true,          // Encrypt all cookie values
```

### 2.1 EncryptCookies Middleware

```php
// App\Http\Kernel.php — must be first in web group
protected $middlewareGroups = [
    'web' => [
        \App\Http\Middleware\EncryptCookies::class,
        \Illuminate\Session\Middleware\StartSession::class,
        // ...
    ],
];
```

### 2.2 Sensitive Action Re-Authentication

Financial operations, permission changes, and admin actions must require password re-confirmation — not just session presence.

```php
Route::post('/settlement/approve', [SettlementController::class, 'approve'])
    ->middleware(['auth', 'password.confirm']);
```

---

## Rule 3 — Authentication

### 3.1 Use Official Auth Packages — Never Build Custom Auth From Scratch

| Package           | Use Case                                                                  |
| ----------------- | ------------------------------------------------------------------------- |
| Laravel Breeze    | Simple web apps — login, registration, password reset, email verification |
| Laravel Fortify   | Headless / API + web — all Breeze features + 2FA                          |
| Laravel Jetstream | Full-stack starter — Fortify + UI + team management                       |
| Laravel Sanctum   | SPA and mobile API auth — cookie-based + API tokens                       |
| Laravel Passport  | OAuth2 provider for third-party integrations                              |

### 3.2 Authentication Rules

- Enable **two-factor authentication (2FA)** via Fortify for all admin and financial roles.
- Enforce **email verification** before granting access to financial features.
- Use **bcrypt (cost ≥ 12)** or **Argon2id** for password hashing — never MD5 or SHA-1.
- Implement **account lockout** after 5 consecutive failed login attempts.
- **Rate-limit** login, registration, password reset, and OTP verification endpoints.
- Use **generic error messages** on auth failures — never reveal whether a user exists.
- API tokens must have **expiry** and **minimum required scopes** (principle of least privilege).
- Track **device/session** information for sensitive role authentications.

### 3.3 Enumeration Prevention

```php
// ❌ BAD — reveals user existence
return response()->json(['error' => 'User not found'], 404);

// ✅ GOOD — generic response for both existing and non-existing users
return response()->json(['message' => 'If an account exists, a reset link has been sent.'], 200);
```

Apply to: login, password reset, email verification, and all lookup endpoints.

---

## Rule 4 — Authorization & Access Control

> **Route middleware is NOT sufficient authorization. Every controller, service, export, background job, and API must perform explicit authorization checks.**

### 4.1 Use Gates and Policies — Everywhere

```php
class InvoicePolicy
{
    public function view(User $user, Invoice $invoice): bool
    {
        return $user->branch_id === $invoice->branch_id
            && $user->tenant_id === $invoice->tenant_id
            && $user->can('view-invoices');
    }

    public function approve(User $user, Invoice $invoice): bool
    {
        return $user->hasRole('finance-manager')
            && $invoice->branch_id === $user->branch_id
            && $invoice->status === 'pending';
    }
}
```

### 4.2 Authorization Per Layer

| Layer              | Method                                     | Notes                                                 |
| ------------------ | ------------------------------------------ | ----------------------------------------------------- |
| Controllers        | `Gate::authorize()` / `$this->authorize()` | First line after validation                           |
| Service Classes    | Gate / Policy check                        | Services may run from jobs — must re-check            |
| Eloquent           | Global scope for tenant/branch             | Auto-enforce ownership on all queries                 |
| Exports            | Explicit permission check                  | Exports bypass UI — must be independently authorized  |
| Background Jobs    | Re-check at job execution time             | Permissions may change between dispatch and execution |
| API Endpoints      | Sanctum/Passport scopes + policy           | Declare required scopes on every route                |
| WebSocket Events   | Channel auth in `routes/channels.php`      | Private channels must verify ownership                |
| Scheduled Commands | Context-scoped, logged                     | All super-admin bypasses must be audit logged         |

### 4.3 Maker-Checker Approval Workflow

These operations require dual authorization with full audit trail:

- Settlement reversal or cancellation
- Credit-limit override
- Stock adjustment
- Sensitive data export approval
- Rate override on financial transactions
- Manual ledger adjustments
- User role / permission changes

### 4.4 Never Assume UI Restrictions Are Security

```php
// ❌ WRONG — relies on frontend to hide the button
public function destroy(Invoice $invoice)
{
    $invoice->delete(); // No authorization check
}

// ✅ CORRECT — server-side authorization always
public function destroy(Invoice $invoice)
{
    $this->authorize('delete', $invoice);
    $invoice->delete();
}
```

---

## Rule 5 — Mass Assignment Protection

### 5.1 Never Do This

```php
// ❌ DANGEROUS
$user->forceFill($request->all())->save();
User::create($request->all());
$model->fill($request->all());
Model::unguard();
protected $guarded = [];
```

### 5.2 Always Do This

```php
// ✅ SAFE — use only validated/whitelisted fields
$user->update($request->only(['name', 'email', 'phone']));
$user->update($request->validated()); // After Form Request restricts allowed fields
```

### 5.3 Fields That Must NEVER Be in `$fillable`

| Category                 | Examples                                                                |
| ------------------------ | ----------------------------------------------------------------------- |
| Financial derived fields | `paid_amount`, `balance_amount`, `settlement_amount`, `ledger_total`    |
| Approval / state flags   | `is_approved`, `approval_status`, `is_admin`, `role`                    |
| Audit / system columns   | `created_by`, `updated_by`, `created_ip`, `deleted_at`                  |
| Status flags             | `payment_status`, `settlement_status`, `account_status`                 |
| Scoping IDs              | `branch_id`, `tenant_id` — must come from server context, never request |

### 5.4 Domain Service Pattern for Derived Values

```php
class SettlementService
{
    public function process(Settlement $settlement, User $actor): void
    {
        DB::transaction(function () use ($settlement, $actor) {
            $settlement = Settlement::lockForUpdate()->findOrFail($settlement->id);

            // All amounts computed server-side — NEVER from request input
            $settlement->paid_amount  = $this->calculatePaid($settlement);
            $settlement->balance      = $this->calculateBalance($settlement);
            $settlement->status       = 'processed';
            $settlement->processed_by = $actor->id;
            $settlement->processed_at = now();
            $settlement->save();
        });
    }
}
```

---

## Rule 6 — SQL Injection Prevention

### 6.1 Eloquent ORM — Default Protection

```php
// ✅ SAFE — Eloquent uses parameterized queries automatically
User::where('email', $email)->get();
// Executes: SELECT * FROM `users` WHERE `email` = ?
```

### 6.2 Raw Queries — Always Use Bindings

```php
// ❌ VULNERABLE — string concatenation
User::whereRaw('email = "' . $request->input('email') . '"')->get();
DB::statement('DELETE FROM users WHERE id = ' . $id);

// ✅ SAFE — positional binding
User::whereRaw('email = ?', [$request->input('email')])->get();

// ✅ SAFE — named binding
User::whereRaw('email = :email', ['email' => $request->input('email')])->get();

// ✅ SAFE — DB::select with bindings
DB::select('SELECT * FROM users WHERE branch_id = ?', [$branchId]);
```

### 6.3 Column Name Injection — Whitelist Validation

```php
// ❌ VULNERABLE — user-controlled column name
User::query()->orderBy($request->input('sortBy'))->get();

// ✅ SAFE — whitelist validation first
$request->validate(['sortBy' => 'in:name,email,created_at,updated_at']);
User::query()->orderBy($request->validated()['sortBy'])->get();
```

### 6.4 Validation Rule Column Injection

```php
// ❌ VULNERABLE — user-supplied column name
$request->validate([
    'id' => Rule::unique('users')->ignore($id, $request->input('colname'))
]);

// ✅ SAFE — hardcode the column name
$request->validate([
    'id' => Rule::unique('users', 'id')->ignore($id)
]);
```

---

## Rule 7 — Cross-Site Scripting (XSS) Prevention

### 7.1 Blade Template Rules

```blade
{{-- ✅ SAFE — auto-escaped with htmlspecialchars --}}
{{ $user->name }}
{{ request()->input('q') }}

{{-- ❌ DANGEROUS — raw unescaped output --}}
{!! $userContent !!}
{!! request()->input('somedata') !!}

{{-- Only use {!! !!} for trusted, system-generated HTML. Never for user input. --}}
```

### 7.2 JavaScript Variable Injection

```blade
{{-- ❌ DANGEROUS --}}
<script>var user = "{{ $user->name }}";</script>

{{-- ✅ SAFE — use @json directive --}}
<script>var user = @json($user->name);</script>
```

### 7.3 XSS Surface Areas Checklist

| Surface                 | Risk         | Mitigation                                   |
| ----------------------- | ------------ | -------------------------------------------- |
| Blade `{{ }}` output    | Low          | Default safe — auto-escaped                  |
| Blade `{!! !!}` output  | **CRITICAL** | Never use on user input                      |
| JSON in `<script>` tags | High         | Use `@json` directive                        |
| Markdown / rich text    | High         | Sanitize with HTMLPurifier before rendering  |
| PDF generators (DOMPDF) | Medium       | Escape all user values before PDF generation |
| Email templates         | Medium       | Always use `{{ }}` in Blade email templates  |
| CSV/Excel export values | Medium       | Sanitize formula-injection characters        |

### 7.4 Content Security Policy

```php
// SecurityHeaders middleware
$response->headers->set('Content-Security-Policy',
    "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline';"
);
```

---

## Rule 8 — Cross-Site Request Forgery (CSRF)

### 8.1 CSRF Middleware

```php
// App\Http\Kernel.php — VerifyCsrfToken MUST be in web middleware group
protected $middlewareGroups = [
    'web' => [
        \App\Http\Middleware\VerifyCsrfToken::class,
        // ...
    ],
];
```

```blade
{{-- All POST/PUT/PATCH/DELETE forms must include @csrf --}}
<form method="POST" action="/profile">
    @csrf
    ...
</form>
```

### 8.2 CSRF Exclusion Rules

```php
// App\Http\Middleware\VerifyCsrfToken.php
protected $except = [
    'api/*',             // Stateless — protected by Sanctum/Passport tokens
    'webhooks/stripe',   // Verified by Stripe signature header
    // NEVER: 'admin/*', '*', 'payment/*' — never exclude broadly
];
```

### 8.3 AJAX / SPA CSRF

```js
// axios global config (resources/js/bootstrap.js)
window.axios.defaults.headers.common["X-CSRF-TOKEN"] = document
    .querySelector('meta[name="csrf-token"]')
    .getAttribute("content");

// For Sanctum SPA auth — call csrf-cookie first
await axios.get("/sanctum/csrf-cookie");
```

---

## Rule 9 — File Upload Security

### 9.1 Always Validate Type, Extension & Size

```php
$request->validate([
    'document' => [
        'required',
        'file',
        'max:2048',                              // 2MB max — prevent storage DOS
        'mimes:pdf,jpg,jpeg,png',                // Whitelist MIME types
        'mimetypes:application/pdf,image/jpeg,image/png', // Double-check content
    ],
]);
```

### 9.2 Safe Storage Rules

```php
// ❌ DANGEROUS — user-controlled path and filename
$request->file('file')->storeAs('uploads', $request->input('filename'));

// ✅ SAFE — randomized name, private storage
$path = $request->file('file')->store('uploads', 'private');

// ✅ SAFE — controlled extension, UUID filename
$filename = Str::uuid() . '.' . $request->file('file')->extension();
$path = $request->file('file')->storeAs('uploads', $filename, 'private');
```

- Never store uploads in `public/` — use `storage/app/private/` or S3.
- Always use `basename()` on any user-supplied filename to strip directory traversal.
- Never execute uploaded content.

### 9.3 Dangerous File Types — Never Allow

```
PHP, PHP3-7, PHTML, PHAR, SH, PY, RB, EXE, BAT, CMD
```

These can lead to Remote Code Execution if stored in reachable paths.

### 9.4 ZIP & XML Files

```php
// Disable XML external entities before any XML processing
libxml_disable_entity_loader(true);
$dom = new DOMDocument();
$dom->loadXML($content, LIBXML_NONET | LIBXML_NOENT);
```

Avoid processing ZIP (zip bomb) and XML (XXE, billion laughs) where possible. If required, enforce strict size limits.

---

## Rule 10 — Path Traversal & Open Redirection

### 10.1 Path Traversal Prevention

```php
// ❌ VULNERABLE — attacker sends: ../../.env
return response()->download(storage_path('content/') . $request->input('filename'));

// ✅ SAFE — strip directory traversal
$filename = basename($request->input('filename'));
return response()->download(storage_path('content/') . $filename);

// ✅ BEST — validate filename format + check existence
$request->validate(['file' => 'required|string|regex:/^[\w\-\.]+$/']);
$path = storage_path('content/') . basename($request->validated()['file']);
abort_unless(file_exists($path), 404);
return response()->download($path);
```

### 10.2 Open Redirection Prevention

```php
// ❌ VULNERABLE — attacker: ?url=http://evil.com
return redirect($request->input('url'));

// ✅ SAFE — validate against named routes
$allowed = ['dashboard', 'profile', 'invoices'];
$route = $request->input('redirect', 'dashboard');
abort_unless(in_array($route, $allowed, true), 400);
return redirect()->route($route);

// ✅ SAFE — enforce same-origin
$url = $request->input('redirect');
if (!str_starts_with($url, config('app.url'))) {
    $url = route('dashboard');
}
return redirect($url);
```

---

## Rule 11 — Command, Object & Other Injections

### 11.1 Command Injection

```php
// ❌ VULNERABLE
exec('whois ' . $request->input('domain'));

// ✅ SAFE — escape arguments
$domain = escapeshellarg($request->input('domain'));
exec('whois ' . $domain);

// ✅ BEST — use Symfony Process (no shell interpolation)
use Symfony\Component\Process\Process;
$process = new Process(['whois', $domain]);
$process->run();
```

### 11.2 Object / Eval / Extract Injection

```php
// ❌ DANGEROUS — never pass user input to these
unserialize($request->input('data'));   // Object injection / RCE
eval($request->input('data'));           // Remote code execution
extract($request->all());               // Variable hijacking

// ✅ SAFE alternatives
$data = json_decode($request->input('data'), true); // Instead of unserialize
// Use a template engine instead of eval()
// Explicitly list variables instead of extract()
```

### 11.3 Other Injection Types

- **LDAP**: Use `ldap_escape()` on all user input in LDAP queries.
- **NoSQL/MongoDB**: Never accept user input as query operator keys (`$where`, `$gt`).
- **Email headers**: Sanitize `To`, `CC`, `Subject` fields — strip newline characters to prevent header injection.

---

## Rule 12 — Rate Limiting

### 12.1 Rate Limiter Definitions

```php
// AppServiceProvider or RouteServiceProvider boot()
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

// Login — strict, keyed by email + IP
RateLimiter::for('login', function (Request $request) {
    return [
        Limit::perMinute(5)->by($request->input('email')),
        Limit::perMinute(10)->by($request->ip()),
    ];
});

// OTP verification
RateLimiter::for('otp', function (Request $request) {
    return Limit::perMinute(3)->by($request->user()?->id ?: $request->ip());
});

// Financial operations
RateLimiter::for('settlement', function (Request $request) {
    return Limit::perMinute(3)->by($request->user()?->id ?: $request->ip());
});

// Exports
RateLimiter::for('exports', function (Request $request) {
    return Limit::perHour(10)->by($request->user()->id);
});
```

### 12.2 Rate Limit Coverage

| Endpoint             | Per Minute | Per Hour | Key By       |
| -------------------- | ---------- | -------- | ------------ |
| Login                | 5          | —        | email + IP   |
| Password Reset       | 3          | —        | email + IP   |
| Email Verification   | 5          | —        | user ID      |
| OTP Verification     | 3          | —        | user ID + IP |
| API (general)        | 60         | —        | user ID      |
| Settlement / Payment | 3          | —        | user ID      |
| Data Exports         | —          | 10       | user ID      |
| Search Endpoints     | 30         | —        | user ID + IP |
| Registration         | 5          | —        | IP           |

### 12.3 Global API Rate Limiting

```php
// App\Http\Kernel.php
protected $middlewareGroups = [
    'api' => [
        'throttle:60,1',  // 60 requests per minute for all API routes
        // ...
    ],
];
```

---

## Rule 13 — Financial Integrity & Transaction Security

> **All financial calculations execute server-side. Never trust client-side totals, balances, or calculated amounts.**

### 13.1 Transaction Pattern

```php
use Illuminate\Support\Facades\DB;

DB::transaction(function () use ($settlement) {
    // Lock the row — prevents concurrent modification / race conditions
    $settlement = Settlement::lockForUpdate()->findOrFail($settlement->id);

    // Validate invariants INSIDE the transaction
    throw_if($settlement->status !== 'pending', new InvalidStateException('Already processed'));
    throw_if(
        $settlement->amount > $settlement->supplier->available_credit,
        new InsufficientCreditException()
    );

    // All computations server-side
    $settlement->paid_amount  = $settlement->amount;
    $settlement->balance      = 0;
    $settlement->status       = 'processed';
    $settlement->processed_at = now();
    $settlement->processed_by = auth()->id();
    $settlement->save();

    // Ledger entry in same transaction — atomicity guaranteed
    LedgerEntry::create([
        'settlement_id' => $settlement->id,
        'amount'        => $settlement->amount,
        'type'          => 'debit',
        'created_by'    => auth()->id(),
    ]);

}, 3); // Retry up to 3 times on deadlock
```

### 13.2 Financial Invariants — Must Always Hold

- `settlement_amount` must be ≤ `available_balance` (validated server-side inside the transaction)
- `quantity` must be > 0 — never negative
- `discount` must be between 0% and 100% inclusive
- Line item totals must reconcile with the invoice total (server-side cross-check)
- No negative balances allowed after any operation
- Currency amounts must use correct DB precision: `DECIMAL(15,4)`
- Tax calculations must be reproducible and consistent server-side

### 13.3 Financial Reversal Rules

```php
// ❌ WRONG — silently overwrites financial history
$settlement->amount = 0;
$settlement->save();

// ✅ CORRECT — compensating reversal entry preserves history
DB::transaction(function () use ($settlement, $reason) {
    SettlementReversal::create([
        'original_settlement_id' => $settlement->id,
        'reversed_amount'        => $settlement->amount,
        'reason'                 => $reason,
        'reversed_by'            => auth()->id(),
        'reversed_at'            => now(),
    ]);

    $settlement->status = 'reversed';
    $settlement->save();
});
```

**Never rewrite historical financial truth. Use reversal entries, cancellation flows, and compensating transactions.**

### 13.4 Replay Attack & Idempotency

```php
// All settlement/payment endpoints require an idempotency key
$key = $request->header('Idempotency-Key');
abort_if(empty($key), 400, 'Idempotency-Key header required');

$existing = ProcessedRequest::where('idempotency_key', $key)
    ->where('created_at', '>', now()->subHours(24))
    ->first();

if ($existing) {
    return response()->json($existing->response_payload); // Replay safe
}
```

- OTP verifications must be single-use and expire after a configured duration.
- Queue jobs processing payments must be **idempotent** and replay-safe.
- Duplicate settlement detection must check the idempotency key within a time window.

---

## Rule 14 — IDOR Prevention & Tenant/Branch Isolation

### 14.1 Never Trust IDs from Client Requests

```php
// ❌ VULNERABLE — IDOR: any user can access any invoice
$invoice = Invoice::findOrFail($request->input('invoice_id'));

// ✅ SAFE — scoped to authenticated user's branch + tenant
$invoice = Invoice::where('branch_id', auth()->user()->branch_id)
    ->where('tenant_id', auth()->user()->tenant_id)
    ->findOrFail($request->route('invoice'));
```

### 14.2 Global Tenant Scope

```php
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (auth()->check()) {
            $builder->where($model->getTable() . '.tenant_id', auth()->user()->tenant_id);
        }
    }
}

// Apply to all tenant-scoped models
protected static function booted(): void
{
    static::addGlobalScope(new TenantScope());
}
```

### 14.3 Isolation Rules

- All reports, exports, APIs, dashboards, and search results must enforce branch and tenant scope.
- Cross-branch access requires explicit authorization and must be audit logged.
- **Never trust `branch_id` or `tenant_id` from the frontend request** — always derive from the authenticated session.
- Use UUIDs for public-facing sensitive resource IDs — avoid sequential predictable IDs.
- Soft-deleted records must remain authorization-scoped — `withTrashed()` requires elevated permission.

---

## Rule 15 — Audit Trail & Logging

### 15.1 Mandatory Audit Events

| Category           | Events to Log                                                              |
| ------------------ | -------------------------------------------------------------------------- |
| Authentication     | Login success, failure, logout, password change, 2FA change, account lock  |
| Authorization      | Permission denied, super-admin bypass, role/permission changes             |
| Financial          | Settlement create/approve/reverse, payment processing, credit-limit change |
| Exports            | Every export — user, filters, timestamp, record count                      |
| Admin Actions      | User create/update/delete, branch create, tenant config changes            |
| Approval Workflows | Maker submission, checker approve/reject with actor + timestamp            |
| Data Deletion      | Soft-delete and hard-delete with actor and reason                          |
| Config Changes     | Rate changes, tax changes, system config modifications                     |

### 15.2 Audit Log Structure

```php
AuditLog::create([
    'tenant_id'   => auth()->user()->tenant_id,
    'branch_id'   => auth()->user()->branch_id,
    'actor_id'    => auth()->id(),
    'actor_ip'    => request()->ip(),
    'action'      => 'settlement.approved',
    'entity_type' => Settlement::class,
    'entity_id'   => $settlement->id,
    'before'      => $settlement->getOriginal(), // Snapshot before change
    'after'       => $settlement->getAttributes(), // Snapshot after change
    'metadata'    => ['approval_note' => $note],
    'created_at'  => now(),
]);
```

### 15.3 Logging Architecture Rules

- Separate log channels: `security`, `financial`, `application`, `audit`.
- Audit log failures must **never fail silently** — use a fallback channel and alert on failure.
- Logs must be centralized to a SIEM or aggregation system (Elastic, Splunk, etc.).
- **Never log**: passwords, API keys, card numbers, full session tokens, or raw secrets.

```php
// config/logging.php — separate channels
'channels' => [
    'audit'     => ['driver' => 'daily', 'path' => storage_path('logs/audit.log')],
    'financial' => ['driver' => 'daily', 'path' => storage_path('logs/financial.log')],
    'security'  => ['driver' => 'daily', 'path' => storage_path('logs/security.log')],
],
```

---

## Rule 16 — Queue & Job Security

### 16.1 Job Security Pattern

```php
class ProcessSettlementJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly int    $settlementId,
        private readonly int    $actorId,   // Store ID — not the User object
        private readonly string $tenantId,
    ) {}

    public function handle(): void
    {
        // RE-VALIDATE permissions at job execution time — not dispatch time
        $actor = User::findOrFail($this->actorId);
        abort_unless($actor->can('process-settlement'), 403);

        // RE-SCOPE to correct tenant
        $settlement = Settlement::where('tenant_id', $this->tenantId)
            ->lockForUpdate()
            ->findOrFail($this->settlementId);

        // Idempotency — job may retry
        if ($settlement->status === 'processed') return;

        // Always within a transaction
        DB::transaction(fn () => $this->doProcess($settlement, $actor));
    }
}
```

### 16.2 Queue Security Rules

- Never trust serialized payloads blindly — validate at execution time.
- Jobs must be **idempotent** — safe to retry on failure.
- Re-check actor permissions inside the job, not just at dispatch time.
- Re-scope all queries by tenant and branch inside the job.
- Every job must write an audit log entry.
- Use encrypted queue connections for sensitive financial job data.
- Never serialize raw financial amounts or PII in job payloads — use IDs.

---

## Rule 17 — Security Headers & Infrastructure

### 17.1 Security Headers Middleware

```php
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Content-Security-Policy',
            "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:;"
        );

        if (config('app.env') === 'production') {
            $response->headers->set('Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        return $response;
    }
}
```

Register in `App\Http\Kernel.php` web middleware group.

### 17.2 Required Security Headers Reference

| Header                      | Required Value                        | Purpose                        |
| --------------------------- | ------------------------------------- | ------------------------------ |
| `X-Frame-Options`           | `SAMEORIGIN`                          | Prevent clickjacking           |
| `X-Content-Type-Options`    | `nosniff`                             | Prevent MIME type sniffing     |
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains` | Enforce HTTPS                  |
| `Content-Security-Policy`   | `default-src 'self'` (tuned per app)  | Mitigate XSS                   |
| `Referrer-Policy`           | `strict-origin-when-cross-origin`     | Control referrer leakage       |
| `Permissions-Policy`        | `geolocation=(), microphone=()`       | Restrict browser features      |
| `Cache-Control`             | `no-store` on sensitive pages         | Prevent sensitive data caching |

### 17.3 CORS Configuration

```php
// config/cors.php — NEVER use wildcard '*' in production
return [
    'paths'                => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_origins'      => [env('FRONTEND_URL', 'https://app.yourdomain.com')],
    'allowed_methods'      => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
    'allowed_headers'      => ['Content-Type', 'Authorization', 'X-Requested-With', 'X-CSRF-TOKEN'],
    'exposed_headers'      => [],
    'max_age'              => 3600,
    'supports_credentials' => true, // Only when using cookie-based Sanctum SPA auth
];
```

### 17.4 Production Infrastructure Checklist

- `APP_DEBUG=false`
- HTTPS enforced at server/load-balancer level
- Encrypted backups with restricted, audited access
- Centralized logging to SIEM
- Secret rotation policy in place
- Database encrypted at rest

---

## Rule 18 — Secret Management

### 18.1 Secrets Must Never Exist In

- Source code
- Frontend bundles
- Application logs
- Screenshots or documentation
- Exported configs

### 18.2 What Counts as a Secret

`APP_KEY` · `DB_PASSWORD` · JWT signing keys · Third-party API keys · OAuth client credentials · Encryption keys · SSL certificates

### 18.3 Correct Storage Methods

```env
# .env — environment variables for all secrets
APP_KEY=base64:...
DB_PASSWORD=...
STRIPE_SECRET=...
```

```bash
# .gitignore — always include
.env
.env.*
!.env.example
*.pem
*.key
*.p12
```

For production: use **AWS Secrets Manager**, **HashiCorp Vault**, or equivalent — not plain environment files on disk.

### 18.4 Secret Rotation Policy

- `APP_KEY` — rotate immediately on compromise (invalidates sessions/cookies).
- Database passwords — rotate on schedule (minimum annually) and immediately on compromise.
- Third-party API keys — enforce expiry dates and rotate before expiry.
- JWT secrets — rotate on a schedule with a token invalidation strategy.

---

## Rule 19 — Database Integrity & Soft Delete

### 19.1 Migration Requirements

```php
Schema::create('invoices', function (Blueprint $table) {
    $table->uuid('id')->primary();               // UUIDs — no predictable sequential IDs
    $table->unsignedBigInteger('tenant_id');
    $table->unsignedBigInteger('branch_id');
    $table->unsignedBigInteger('supplier_id');
    $table->decimal('amount', 15, 4);            // Financial precision
    $table->decimal('paid_amount', 15, 4)->default(0);
    $table->decimal('balance_amount', 15, 4)->default(0);
    $table->string('status')->default('draft');
    $table->unsignedBigInteger('created_by');
    $table->string('created_ip', 45)->nullable();
    $table->timestamps();
    $table->softDeletes();                        // Never hard-delete financial records

    // Foreign keys — enforce referential integrity
    $table->foreign('tenant_id')->references('id')->on('tenants');
    $table->foreign('supplier_id')->references('id')->on('suppliers');
    $table->foreign('created_by')->references('id')->on('users');

    // Indexes for performance and integrity
    $table->index(['tenant_id', 'branch_id', 'status']);
    $table->unique(['tenant_id', 'invoice_number']); // Prevent duplicates
});
```

### 19.2 Soft Delete Rules

- Financial records (invoices, settlements, payments, ledger) must use soft deletes — physical deletion is **forbidden**.
- Soft-deleted records must remain authorization-scoped (branch/tenant scope applies to trashed records).
- Soft-deleted records must remain visible in audit trails.
- Prefer **cancellation** or **reversal** flows over any deletion for financial documents.
- `withTrashed()` queries require elevated permission.

---

## Rule 20 — API Security

### 20.1 Every API Endpoint Must Have All of These

```php
Route::middleware(['auth:sanctum', 'throttle:api'])
    ->group(function () {
        Route::post('/settlements', [SettlementController::class, 'store'])
            ->middleware('ability:settlements:create'); // Sanctum scope
    });

// Controller
public function store(StoreSettlementRequest $request): JsonResponse // Form Request = validation
{
    $this->authorize('create', Settlement::class); // Policy = authorization

    // ... domain logic ...

    AuditLog::write('settlement.created', $settlement); // Audit

    return response()->json($settlement, 201);
}
```

### 20.2 API Error Responses — Never Expose Internals

```php
// ✅ SAFE — structured error response
return response()->json([
    'message' => 'The given data was invalid.',
    'errors'  => $validator->errors(),
], 422);

// ❌ NEVER in production
return response()->json([
    'exception' => $e->getMessage(),  // Stack trace leak
    'file'      => $e->getFile(),     // Path disclosure
    'sql'       => $query,            // Schema disclosure
], 500);
```

### 20.3 API Security Rules

- Every API endpoint: authentication + authorization + validation + rate limiting + audit logging.
- API tokens: expiry, minimum scopes, and rotation capability.
- API pagination must enforce a maximum page size (prevent mass data extraction).
- Responses must never expose: stack traces, SQL errors, internal file paths, or debug data.

---

## Rule 21 — Export Security

- All exports must validate permissions before generating — never rely on route middleware alone.
- Every export must create an audit log entry with: user, timestamp, filters applied, record count.
- Exports must enforce the same branch/tenant scope as the UI — never broader.
- Large exports must be processed asynchronously (queued jobs) with progress tracking.
- Sensitive exports (financial data, PII) may require a secondary approval step.
- Export rate limiting: max 10 exports/hour per user.

### 21.1 CSV Formula Injection Prevention

```php
// Sanitize values that begin with =, +, -, @ to prevent spreadsheet formula injection
function sanitizeCsvValue(string $value): string
{
    if (preg_match('/^[=+\-@\t\r\n]/', $value)) {
        return "'" . $value; // Prepend single quote to neutralize
    }
    return $value;
}
```

---

## Rule 22 — Cache Security

### 22.1 What Must Never Be Cached

- Session tokens and authentication tokens
- Authorization decisions (permissions may change)
- Financial summaries with real-time balance data
- Sensitive PII without encryption
- Data scoped to a specific user/tenant in a shared cache store

### 22.2 Cache Key Isolation

```php
// Always namespace by tenant to prevent cross-tenant leakage
$cacheKey = 'tenant:' . $tenantId . ':branch:' . $branchId . ':report:' . $reportType;
Cache::remember($cacheKey, now()->addMinutes(5), fn () => $this->buildReport());
```

### 22.3 Cache Invalidation on State Changes

```php
// After permission changes
Cache::forget('user:' . $userId . ':permissions');

// After settlement processing
Cache::forget('tenant:' . $tenantId . ':branch:' . $branchId . ':balance');

// After rate/config changes
Cache::tags(['tenant:' . $tenantId . ':calculations'])->flush();
```

---

## Rule 23 — Security Monitoring

The system must monitor and alert on:

- Failed login attempts (threshold: 5 in 10 minutes per IP)
- Suspicious API activity (unusual volume, unusual hours)
- Excessive export requests (above rate limit threshold)
- Repeated validation failures from a single actor
- Privilege escalation attempts
- Excessive settlement retries

---

## Master Pre-Production Security Checklist

### Application Basics

- [ ] `APP_DEBUG=false` in production
- [ ] `APP_KEY` generated and stored securely — not in version control
- [ ] `APP_ENV=production`
- [ ] File permissions set correctly (`775` dirs, `664` files, `640` for `.env`)
- [ ] `composer audit` and `npm audit` passing

### Cookie & Session

- [ ] `http_only=true` in `config/session.php`
- [ ] `same_site=lax` or `strict`
- [ ] `secure=null` (auto) or `true`
- [ ] Session lifetime set to 15 minutes (financial app)
- [ ] `EncryptCookies` middleware enabled
- [ ] Cookie domain scoped correctly

### Authentication

- [ ] Official auth starter kit in use (Breeze/Fortify/Jetstream)
- [ ] 2FA enabled for admin and financial roles
- [ ] Password hashing uses bcrypt (cost ≥12) or Argon2id
- [ ] Generic error messages on auth failures (no enumeration)
- [ ] Account lockout after 5 failed attempts
- [ ] API tokens have expiry and minimum required scopes

### Authorization

- [ ] All controllers use `Gate::authorize()` or `$this->authorize()`
- [ ] All services re-check permissions independently
- [ ] All background jobs re-validate permissions at execution time
- [ ] All exports have independent permission checks
- [ ] Maker-checker workflow implemented for critical operations
- [ ] Super-admin bypasses logged in audit trail

### Mass Assignment

- [ ] No derived/financial fields in `$fillable`
- [ ] No `Model::unguard()` calls in production code
- [ ] No `$request->all()` passed directly to `create()`/`update()`
- [ ] Only `$request->only()` or `$request->validated()` used

### SQL Injection

- [ ] No string-concatenated SQL queries anywhere
- [ ] Raw queries use positional or named bindings
- [ ] Column names from user input validated against whitelist
- [ ] `Rule::unique()` column names hardcoded — not from request

### XSS

- [ ] No `{!! !!}` used on user-supplied data in Blade templates
- [ ] JavaScript variable injection uses `@json` directive
- [ ] Markdown/rich text passes through HTMLPurifier before render
- [ ] CSV exports sanitize formula-injection characters
- [ ] Content-Security-Policy header deployed

### CSRF

- [ ] `VerifyCsrfToken` in web middleware group
- [ ] `@csrf` in all POST/PUT/PATCH/DELETE forms
- [ ] CSRF exceptions list reviewed — no broad exclusions
- [ ] `X-CSRF-TOKEN` header configured for AJAX requests

### File Uploads

- [ ] File type and size validated on all upload endpoints
- [ ] Uploads stored outside `public/` directory
- [ ] Filenames randomized — user-supplied names sanitized with `basename()`
- [ ] No executable file types permitted
- [ ] ZIP/XML processing has size limits and entity loading disabled

### Rate Limiting

- [ ] Login endpoint rate-limited by email + IP
- [ ] Password reset rate-limited
- [ ] OTP verification rate-limited
- [ ] API endpoints globally rate-limited
- [ ] Export endpoints rate-limited (max 10/hour)
- [ ] Settlement/payment endpoints rate-limited

### Financial Integrity

- [ ] All financial calculations server-side — no client totals trusted
- [ ] DB transactions used for all financial state changes
- [ ] `lockForUpdate()` applied on financial records in transactions
- [ ] Idempotency keys implemented for settlement and payment endpoints
- [ ] Financial invariants validated inside transactions
- [ ] Reversal/compensating entries used — no silent overwrites
- [ ] No negative balances possible after any operation

### Tenant & IDOR Isolation

- [ ] All queries scoped by `tenant_id` and `branch_id`
- [ ] Global tenant scope applied to all tenant-scoped models
- [ ] `branch_id`/`tenant_id` derived from session — never from request
- [ ] UUIDs used for public-facing sensitive resource IDs
- [ ] Soft-deleted records remain authorization-scoped

### Audit Trail

- [ ] Audit log created for every financial operation
- [ ] Audit log created for every export
- [ ] Audit log created for every approval workflow step
- [ ] Audit log created for every permission/role change
- [ ] Audit log includes: actor, IP, timestamp, before/after snapshots
- [ ] Audit log failures alert — never fail silently

### Security Headers

- [ ] `X-Frame-Options: SAMEORIGIN`
- [ ] `X-Content-Type-Options: nosniff`
- [ ] `Strict-Transport-Security` deployed on HTTPS
- [ ] `Content-Security-Policy` configured
- [ ] `Referrer-Policy` set
- [ ] CORS configured with specific allowed origins — no wildcard

### Secrets & Infrastructure

- [ ] `.env` not in version control (`.gitignore` verified)
- [ ] All secrets in environment variables or secret manager
- [ ] No API keys or passwords in source code or logs
- [ ] Backups encrypted and access restricted
- [ ] Production logs centralized to SIEM
- [ ] Dependencies scanned for CVEs

---

## Quick Reference — Threat to Rule Mapping

| Threat                             | Rule(s)         |
| ---------------------------------- | --------------- |
| SQL Injection                      | Rule 6          |
| XSS                                | Rule 7          |
| CSRF                               | Rule 8          |
| Mass Assignment                    | Rule 5          |
| IDOR / Broken Object Access        | Rule 14         |
| Broken Authentication              | Rule 3          |
| Broken Authorization               | Rule 4          |
| Path Traversal                     | Rule 10         |
| Open Redirect                      | Rule 10         |
| File Upload / RCE                  | Rule 9          |
| Command Injection                  | Rule 11         |
| Race Condition / Double Settlement | Rule 13         |
| Replay Attack                      | Rule 13         |
| Tenant Data Leakage                | Rule 14         |
| Audit Trail Gap                    | Rule 15         |
| Secret Exposure                    | Rule 18         |
| DDoS / API Abuse                   | Rule 12         |
| Mass Data Export / Exfiltration    | Rule 21         |
| Debug/Trace Exposure               | Rule 1, Rule 20 |
| Insecure Session                   | Rule 2          |
| Queue/Job Authorization Bypass     | Rule 16         |
| Clickjacking                       | Rule 17         |
| Cache Data Leakage                 | Rule 22         |

---

_Enterprise Laravel Security Rules v3.0 — OWASP Aligned — FinTech/ERP Grade_

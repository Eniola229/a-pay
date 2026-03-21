@include('components.header')
<meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://js.paystack.co/v1/inline.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">

<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    --green-deep:   #00693e;
    --green-mid:    #009966;
    --green-light:  #e6f7f0;
    --green-glow:   rgba(0, 153, 102, 0.15);
    --red:          #e84545;
    --red-light:    #fff0f0;
    --ink:          #111a14;
    --ink-soft:     #4a5e52;
    --border:       #d4e8dc;
    --surface:      #f7fbf9;
    --white:        #ffffff;
    --mono:         'DM Mono', monospace;
    --sans:         'DM Sans', sans-serif;
    --radius:       12px;
    --shadow-sm:    0 1px 4px rgba(0,0,0,.06), 0 4px 16px rgba(0,0,0,.05);
    --shadow-md:    0 2px 8px rgba(0,0,0,.07), 0 8px 32px rgba(0,0,0,.08);
    --transition:   0.2s cubic-bezier(.4,0,.2,1);
}

body { font-family: var(--sans); background: var(--surface); color: var(--ink); }

/* ── Layout ── */
.nl-wrap {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 24px;
    padding: 28px 24px;
    max-width: 1280px;
    margin: 0 auto;
}

@media (max-width: 960px) {
    .nl-wrap { grid-template-columns: 1fr; }
}

/* ── Sidebar stat card ── */
.stat-card {
    background: linear-gradient(160deg, var(--green-mid) 0%, var(--green-deep) 100%);
    border-radius: var(--radius);
    padding: 28px 22px;
    color: var(--white);
    box-shadow: var(--shadow-md);
    position: sticky;
    top: 20px;
}

.stat-card-title {
    font-size: 11px;
    font-weight: 600;
    letter-spacing: .12em;
    text-transform: uppercase;
    opacity: .7;
    margin-bottom: 22px;
}

.stat-block {
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.15);
    border-radius: 10px;
    padding: 16px 18px;
    margin-bottom: 12px;
}

.stat-block:last-of-type { margin-bottom: 0; }

.stat-num {
    font-size: 36px;
    font-weight: 600;
    line-height: 1;
    letter-spacing: -.02em;
}

.stat-label {
    font-size: 13px;
    font-weight: 500;
    margin-top: 4px;
    opacity: .9;
}

.stat-sub {
    font-size: 11px;
    opacity: .6;
    margin-top: 3px;
}

.session-note {
    margin-top: 18px;
    background: rgba(0,0,0,.15);
    border-radius: 8px;
    padding: 13px 14px;
    font-size: 12px;
    line-height: 1.65;
    opacity: .85;
}

.session-note strong { opacity: 1; }

/* ── Right column ── */
.nl-right { display: flex; flex-direction: column; gap: 20px; }

/* ── Alert banners ── */
.nl-alert {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 14px 16px;
    border-radius: var(--radius);
    font-size: 14px;
    font-weight: 500;
    animation: fadeSlideIn .3s ease both;
}

.nl-alert.success { background: var(--green-light); color: var(--green-deep); border: 1px solid #b3dfc9; }
.nl-alert.error   { background: var(--red-light);   color: var(--red);        border: 1px solid #f5b8b8; }
.nl-alert i { margin-top: 1px; flex-shrink: 0; }
.nl-alert-close { margin-left: auto; cursor: pointer; opacity: .5; transition: opacity var(--transition); background: none; border: none; font-size: 16px; color: inherit; }
.nl-alert-close:hover { opacity: 1; }

/* ── Cards ── */
.nl-card {
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
}

.nl-card-header {
    padding: 18px 22px 16px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    gap: 10px;
}

.nl-card-header .icon {
    width: 34px; height: 34px;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 15px;
    flex-shrink: 0;
}

.icon-red    { background: var(--red-light);   color: var(--red); }
.icon-green  { background: var(--green-light);  color: var(--green-mid); }

.nl-card-header h5 {
    font-size: 15px;
    font-weight: 600;
    color: var(--ink);
}

.nl-card-header p {
    font-size: 12px;
    color: var(--ink-soft);
    margin-top: 1px;
}

.nl-card-body { padding: 22px; }

/* ── Form elements ── */
.field { margin-bottom: 18px; }
.field:last-child { margin-bottom: 0; }

.field label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: var(--ink-soft);
    letter-spacing: .05em;
    text-transform: uppercase;
    margin-bottom: 7px;
}

.field textarea,
.field input[type="text"],
.field input[type="file"] {
    width: 100%;
    padding: 12px 14px;
    border: 1.5px solid var(--border);
    border-radius: 8px;
    font-family: var(--sans);
    font-size: 14px;
    color: var(--ink);
    background: var(--surface);
    outline: none;
    transition: border-color var(--transition), box-shadow var(--transition);
}

.field textarea {
    min-height: 180px;
    resize: vertical;
    line-height: 1.6;
}

.field textarea:focus,
.field input[type="text"]:focus {
    border-color: var(--green-mid);
    background: var(--white);
    box-shadow: 0 0 0 3px var(--green-glow);
}

.field input[type="file"] {
    padding: 10px 12px;
    cursor: pointer;
    background: var(--white);
}

.char-counter {
    text-align: right;
    font-size: 11px;
    color: var(--ink-soft);
    margin-top: 5px;
    font-variant-numeric: tabular-nums;
}

/* ── Template ID field ── */
.template-field-wrap {
    background: var(--green-light);
    border: 1.5px dashed #8ecfb0;
    border-radius: 10px;
    padding: 16px 18px;
}

.template-field-wrap label {
    display: flex;
    align-items: center;
    gap: 7px;
    font-size: 12px;
    font-weight: 600;
    color: var(--green-deep);
    letter-spacing: .05em;
    text-transform: uppercase;
    margin-bottom: 4px;
}

.template-field-wrap .template-desc {
    font-size: 12px;
    color: var(--ink-soft);
    margin-bottom: 10px;
    line-height: 1.55;
}

.template-field-wrap input[type="text"] {
    font-family: var(--mono);
    font-size: 13px;
    letter-spacing: .02em;
    background: var(--white);
    border-color: #b3dfc9;
}

.template-field-wrap input[type="text"]:focus {
    border-color: var(--green-mid);
    box-shadow: 0 0 0 3px var(--green-glow);
}

.template-field-wrap input::placeholder { color: #aac5b5; }

.template-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 10px;
    font-weight: 600;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: var(--green-mid);
    background: rgba(0,153,102,.1);
    border: 1px solid rgba(0,153,102,.2);
    border-radius: 20px;
    padding: 2px 8px;
    margin-left: 6px;
}

/* ── Divider ── */
.field-divider {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 20px 0;
    color: var(--ink-soft);
    font-size: 11px;
    font-weight: 600;
    letter-spacing: .08em;
    text-transform: uppercase;
}

.field-divider::before,
.field-divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--border);
}

/* ── Buttons ── */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-family: var(--sans);
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all var(--transition);
    white-space: nowrap;
}

.btn-green {
    background: var(--green-mid);
    color: var(--white);
    box-shadow: 0 2px 8px rgba(0,153,102,.3);
}

.btn-green:hover:not(:disabled) {
    background: var(--green-deep);
    box-shadow: 0 4px 14px rgba(0,153,102,.4);
    transform: translateY(-1px);
}

.btn-red {
    background: var(--red);
    color: var(--white);
    box-shadow: 0 2px 8px rgba(232,69,69,.25);
}

.btn-red:hover:not(:disabled) {
    background: #c93535;
    box-shadow: 0 4px 14px rgba(232,69,69,.35);
    transform: translateY(-1px);
}

.btn:disabled {
    opacity: .6;
    cursor: not-allowed;
    transform: none !important;
}

.btn .spinner { display: none; }
.btn.loading .spinner { display: inline-block; }
.btn.loading .btn-text { display: none; }

@keyframes spin { to { transform: rotate(360deg); } }
.spinner { width: 14px; height: 14px; border: 2px solid rgba(255,255,255,.35); border-top-color: white; border-radius: 50%; animation: spin .6s linear infinite; }

/* ── Error text ── */
.field-error { font-size: 12px; color: var(--red); margin-top: 5px; }

/* ── Animations ── */
@keyframes fadeSlideIn {
    from { opacity: 0; transform: translateY(-6px); }
    to   { opacity: 1; transform: translateY(0); }
}
</style>

<div id="main-wrapper">
    @include('components.nav-header')
    @include('components.main-header')
    @include('components.admin-sidenav')

    <div class="content-body">
        <div class="nl-wrap">

            {{-- ── Sidebar ── --}}
            <aside>
                <div class="stat-card">
                    <div class="stat-card-title">📊 Newsletter Stats</div>

                    <div class="stat-block">
                        <div class="stat-num">{{ $eligibleUsers }}</div>
                        <div class="stat-label">Eligible Users</div>
                        <div class="stat-sub">Registered from Dec 1, 2025</div>
                    </div>

                    <div class="stat-block">
                        <div class="stat-num">{{ $lowBalanceUsers }}</div>
                        <div class="stat-label">Low Balance Users</div>
                        <div class="stat-sub">Balance below ₦100</div>
                    </div>

                    <div class="session-note">
                        <strong>ℹ️ How sending works</strong><br>
                        Users who messaged in the last <strong>24 hrs</strong> get your message directly.<br><br>
                        Users outside that window receive the <strong>template</strong> you paste below.<br><br>
                        If no template is provided, inactive users are skipped.
                    </div>
                </div>
            </aside>

            {{-- ── Right column ── --}}
            <div class="nl-right">

                {{-- Flash messages --}}
                @if(session('success'))
                    <div class="nl-alert success">
                        <i class="fa fa-circle-check"></i>
                        <span>{{ session('success') }}</span>
                        <button class="nl-alert-close" onclick="this.closest('.nl-alert').remove()">&times;</button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="nl-alert error">
                        <i class="fa fa-circle-xmark"></i>
                        <span>{{ session('error') }}</span>
                        <button class="nl-alert-close" onclick="this.closest('.nl-alert').remove()">&times;</button>
                    </div>
                @endif

                {{-- ── Low Balance Alert Card ── --}}
                <div class="nl-card">
                    <div class="nl-card-header">
                        <div class="icon icon-red"><i class="fa fa-bell"></i></div>
                        <div>
                            <h5>Low Balance Alert</h5>
                            <p>Remind users with a wallet balance below ₦100 to top up</p>
                        </div>
                    </div>

                    <div class="nl-card-body">
                        <form action="{{ route('admin.newsletter.low-balance') }}" method="POST" id="lowBalanceForm">
                            @csrf

                            <div class="field">
                                <div class="template-field-wrap">
                                    <label>
                                        <i class="fa fa-id-card"></i>
                                        Template ID
                                        <span class="template-badge">Optional</span>
                                    </label>
                                    <p class="template-desc">
                                        Paste your Twilio Content Template SID (starts with <code>HX…</code>).
                                        Sent to users who haven't messaged in the last 24 hours.
                                        Leave blank to skip inactive users.
                                    </p>
                                    <input
                                        type="text"
                                        name="template_id"
                                        placeholder="HXxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                                        autocomplete="off"
                                        spellcheck="false"
                                    >
                                    @error('template_id')
                                        <div class="field-error">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <button
                                type="submit"
                                class="btn btn-red"
                                id="lowBalanceBtn"
                                onclick="return confirm('Send low balance alert to {{ $lowBalanceUsers }} users?')"
                            >
                                <span class="spinner"></span>
                                <span class="btn-text"><i class="fa fa-paper-plane"></i> Send Low Balance Alert to {{ $lowBalanceUsers }} Users</span>
                            </button>
                        </form>
                    </div>
                </div>

                {{-- ── Newsletter Card ── --}}
                <div class="nl-card">
                    <div class="nl-card-header">
                        <div class="icon icon-green"><i class="fa fa-envelope"></i></div>
                        <div>
                            <h5>Send Newsletter</h5>
                            <p>Compose and broadcast a WhatsApp message to all eligible users</p>
                        </div>
                    </div>

                    <div class="nl-card-body">
                        <form action="{{ route('admin.newsletter.send') }}" method="POST" id="newsletterForm" enctype="multipart/form-data">
                            @csrf

                            {{-- Message --}}
                            <div class="field">
                                <label>Message</label>
                                <textarea
                                    name="message"
                                    id="message"
                                    placeholder="Write your newsletter message here…"
                                    maxlength="1000"
                                    required
                                ></textarea>
                                <div class="char-counter"><span id="charCount">0</span> / 1000</div>
                                @error('message')
                                    <div class="field-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="field-divider">Delivery options</div>

                            {{-- Template ID --}}
                            <div class="field">
                                <div class="template-field-wrap">
                                    <label>
                                        <i class="fa fa-id-card"></i>
                                        Template ID
                                        <span class="template-badge">Optional</span>
                                    </label>
                                    <p class="template-desc">
                                        Paste your Twilio Content Template SID (starts with <code>HX…</code>).
                                        Users who last messaged <strong>over 24 hours ago</strong> will receive this template instead of the message above.
                                        Leave blank to skip those users.
                                    </p>
                                    <input
                                        type="text"
                                        name="template_id"
                                        placeholder="HXxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                                        autocomplete="off"
                                        spellcheck="false"
                                    >
                                    @error('template_id')
                                        <div class="field-error">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            {{-- Media --}}
                            <div class="field">
                                <label>Attach Media <span style="font-weight:400;text-transform:none;letter-spacing:0;font-size:11px;color:#aaa;">— jpg, png, gif, mp4 · max 16 MB · optional</span></label>
                                <input type="file" name="media" id="media" accept="image/*,video/mp4">
                                @error('media')
                                    <div class="field-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <button
                                type="submit"
                                class="btn btn-green"
                                id="sendNewsletterBtn"
                                onclick="return confirm('Send newsletter to {{ $eligibleUsers }} users?')"
                            >
                                <span class="spinner"></span>
                                <span class="btn-text"><i class="fa fa-paper-plane"></i> Send to {{ $eligibleUsers }} Users</span>
                            </button>
                        </form>
                    </div>
                </div>

            </div>{{-- end nl-right --}}
        </div>{{-- end nl-wrap --}}
    </div>{{-- end content-body --}}
</div>

<script>
// Character counter
document.getElementById('message').addEventListener('input', function () {
    document.getElementById('charCount').textContent = this.value.length;
});

// Loading state helper
function setLoading(formId, btnId) {
    document.getElementById(formId).addEventListener('submit', function () {
        const btn = document.getElementById(btnId);
        btn.classList.add('loading');
        btn.disabled = true;
    });
}

setLoading('newsletterForm',  'sendNewsletterBtn');
setLoading('lowBalanceForm',  'lowBalanceBtn');
</script>

<script src="{{ asset('plugins/common/common.min.js') }}"></script>
<script src="{{ asset('js/custom.min.js') }}"></script>
<script src="{{ asset('js/settings.js') }}"></script>
<script src="{{ asset('js/gleek.js') }}"></script>
<script src="{{ asset('js/styleSwitcher.js') }}"></script>
<script src="{{ asset('plugins/chart.js/Chart.bundle.min.js') }}"></script>
<script src="{{ asset('plugins/circle-progress/circle-progress.min.js') }}"></script>
<script src="{{ asset('plugins/d3v3/index.js') }}"></script>
<script src="{{ asset('plugins/topojson/topojson.min.js') }}"></script>
<script src="{{ asset('plugins/datamaps/datamaps.world.min.js') }}"></script>
<script src="{{ asset('plugins/raphael/raphael.min.js') }}"></script>
<script src="{{ asset('plugins/morris/morris.min.js') }}"></script>
<script src="{{ asset('plugins/moment/moment.min.js') }}"></script>
<script src="{{ asset('plugins/pg-calendar/js/pignose.calendar.min.js') }}"></script>
<script src="{{ asset('plugins/chartist/js/chartist.min.js') }}"></script>
<script src="{{ asset('plugins/chartist-plugin-tooltips/js/chartist-plugin-tooltip.min.js') }}"></script>
<script src="{{ asset('js/dashboard/dashboard-1.js') }}"></script>
</body>
</html>
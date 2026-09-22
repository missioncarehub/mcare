<meta name="dashboard-security-event-url" content="{{ route('account.security-event') }}">
<x-site-favicon />
<script>
    document.documentElement.dataset.dashboardTheme = 'light';
    document.documentElement.style.colorScheme = 'light';
    try {
        window.localStorage.removeItem('mcare-dashboard-theme');
    } catch (error) {
        // Storage is optional.
    }
</script>

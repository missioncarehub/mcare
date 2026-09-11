@props(['sidebarId'])

<button
    type="button"
    class="dashboard-sidebar-collapse"
    data-dashboard-sidebar-collapse
    aria-controls="{{ $sidebarId }}"
    aria-expanded="true"
    aria-label="Collapse sidebar"
    title="Collapse sidebar"
>
    <span class="dashboard-sidebar-collapse-expanded">
        <x-dashboard-icon name="chevron-left" />
    </span>
    <span class="dashboard-sidebar-collapse-collapsed">
        <x-dashboard-icon name="chevron-right" />
    </span>
</button>

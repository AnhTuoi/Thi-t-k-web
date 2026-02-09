// Fontend/js/xulytaikhoan.js
class UserReport {
  constructor() {
    this.API_BASE_URL = '../../api';
    this.currentData = null;
    this.currentReportType = 'summary';
    this.currentFilters = {
      startDate: new Date().toISOString().split('T')[0],
      endDate: new Date().toISOString().split('T')[0],
      role: 'all',
      status: 'all',
      reportType: 'summary',
    };

    // Chart instances
    this.userGrowthChart = null;
    this.roleDistributionChart = null;
    this.statusDistributionChart = null;
    this.newUsersChart = null;
    this.activeUsersChart = null;
    this.segmentsChart = null;
    this.loginActivityChart = null;

    // Table pagination
    this.currentPage = 1;
    this.itemsPerPage = 10;
    this.totalItems = 0;
    this.allUsers = [];

    this.init();
  }

  init() {
    this.setupEventListeners();
    this.setDefaultDates();
    this.loadUserSummary();
    this.loadUserData();
  }

  setupEventListeners() {
    // Filter events
    document
      .getElementById('apply-filters')
      ?.addEventListener('click', () => this.applyFilters());
    document
      .getElementById('reset-filters')
      ?.addEventListener('click', () => this.resetFilters());
    document
      .getElementById('refresh-btn')
      ?.addEventListener('click', () => this.refreshData());

    // Quick period buttons
    document.querySelectorAll('.quick-period-btn').forEach((btn) => {
      btn.addEventListener('click', (e) => {
        const period = e.target.dataset.period;
        this.setQuickPeriod(period);
      });
    });

    // Report type change
    document.getElementById('report-type')?.addEventListener('change', (e) => {
      this.currentReportType = e.target.value;
      this.loadUserData();
    });

    // Tab navigation
    document.querySelectorAll('.tab-button').forEach((tab) => {
      tab.addEventListener('click', (e) => {
        const tabId = e.target.dataset.tab;
        this.switchTab(tabId);
      });
    });

    // Growth period change
    document
      .getElementById('growth-period')
      ?.addEventListener('change', (e) => {
        this.loadUserGrowth(parseInt(e.target.value));
      });

    // Login period change
    document.getElementById('login-period')?.addEventListener('change', (e) => {
      this.loadLoginStatistics(parseInt(e.target.value));
    });

    // Table pagination
    document
      .getElementById('prev-page')
      ?.addEventListener('click', () => this.prevPage());
    document
      .getElementById('next-page')
      ?.addEventListener('click', () => this.nextPage());
    document.getElementById('current-page')?.addEventListener('change', (e) => {
      this.goToPage(parseInt(e.target.value));
    });

    // User search
    document.getElementById('user-search')?.addEventListener('input', (e) => {
      this.filterUsers(e.target.value);
    });

    // User sort
    document.getElementById('user-sort')?.addEventListener('change', (e) => {
      this.sortUsers(e.target.value);
    });

    // Export buttons
    document
      .getElementById('export-btn')
      ?.addEventListener('click', () => this.openExportModal());
    document
      .getElementById('export-users')
      ?.addEventListener('click', () => this.exportUsers());
    document.querySelectorAll('.export-format-btn').forEach((btn) => {
      btn.addEventListener('click', (e) => {
        const format = e.target.dataset.format;
        if (format === 'print') {
          window.print();
        } else {
          this.openExportModal();
        }
      });
    });
  }

  setDefaultDates() {
    const today = new Date();
    const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);

    const startDateInput = document.getElementById('start-date');
    const endDateInput = document.getElementById('end-date');

    if (startDateInput && endDateInput) {
      startDateInput.value = firstDayOfMonth.toISOString().split('T')[0];
      endDateInput.value = today.toISOString().split('T')[0];

      this.currentFilters.startDate = startDateInput.value;
      this.currentFilters.endDate = endDateInput.value;
    }
  }

  setQuickPeriod(period) {
    const today = new Date();
    let startDate, endDate;

    switch (period) {
      case 'today':
        startDate = today;
        endDate = today;
        break;
      case 'week':
        startDate = new Date(today);
        startDate.setDate(today.getDate() - 7);
        endDate = today;
        break;
      case 'month':
        startDate = new Date(today.getFullYear(), today.getMonth(), 1);
        endDate = today;
        break;
      case 'quarter':
        const quarter = Math.floor(today.getMonth() / 3);
        startDate = new Date(today.getFullYear(), quarter * 3, 1);
        endDate = today;
        break;
      case 'year':
        startDate = new Date(today.getFullYear(), 0, 1);
        endDate = today;
        break;
    }

    const startDateInput = document.getElementById('start-date');
    const endDateInput = document.getElementById('end-date');

    if (startDateInput && endDateInput) {
      startDateInput.value = startDate.toISOString().split('T')[0];
      endDateInput.value = endDate.toISOString().split('T')[0];

      this.currentFilters.startDate = startDateInput.value;
      this.currentFilters.endDate = endDateInput.value;

      this.applyFilters();
    }
  }

  toggleFilterCard() {
    const filterCard = document.getElementById('filter-card');
    const filterArrow = document.getElementById('filter-arrow');

    if (filterCard && filterArrow) {
      filterCard.classList.toggle('collapsed');

      if (filterCard.classList.contains('collapsed')) {
        filterArrow.style.transform = 'rotate(180deg)';
      } else {
        filterArrow.style.transform = 'rotate(0deg)';
      }
    }
  }

  applyFilters() {
    const startDateInput = document.getElementById('start-date');
    const endDateInput = document.getElementById('end-date');
    const roleFilter = document.getElementById('role-filter');
    const statusFilter = document.getElementById('status-filter');
    const reportType = document.getElementById('report-type');

    if (startDateInput && endDateInput) {
      this.currentFilters.startDate = startDateInput.value;
      this.currentFilters.endDate = endDateInput.value;
    }

    if (roleFilter) {
      this.currentFilters.role = roleFilter.value;
    }

    if (statusFilter) {
      this.currentFilters.status = statusFilter.value;
    }

    if (reportType) {
      this.currentFilters.reportType = reportType.value;
      this.currentReportType = reportType.value;
    }

    // Update filter summary
    this.updateFilterSummary();

    // Load data with new filters
    this.loadUserData();

    this.showToast(
      'Đã áp dụng bộ lọc',
      'Dữ liệu đang được làm mới...',
      'success',
    );
  }

  resetFilters() {
    this.setDefaultDates();

    const roleFilter = document.getElementById('role-filter');
    const statusFilter = document.getElementById('status-filter');
    const reportType = document.getElementById('report-type');

    if (roleFilter) roleFilter.value = 'all';
    if (statusFilter) statusFilter.value = 'all';
    if (reportType) reportType.value = 'summary';

    this.currentFilters = {
      startDate:
        document.getElementById('start-date')?.value ||
        new Date().toISOString().split('T')[0],
      endDate:
        document.getElementById('end-date')?.value ||
        new Date().toISOString().split('T')[0],
      role: 'all',
      status: 'all',
      reportType: 'summary',
    };

    this.currentReportType = 'summary';
    this.updateFilterSummary();
    this.loadUserData();

    this.showToast(
      'Đã đặt lại bộ lọc',
      'Tất cả bộ lọc đã được đặt về mặc định',
      'success',
    );
  }

  updateFilterSummary() {
    const filterSummary = document.getElementById('filter-summary');
    if (filterSummary) {
      const roleText =
        this.currentFilters.role === 'all'
          ? 'Tất cả vai trò'
          : this.currentFilters.role === 'khach_hang'
            ? 'Khách hàng'
            : this.currentFilters.role === 'nhan_vien'
              ? 'Nhân viên'
              : 'Quản trị viên';

      const statusText =
        this.currentFilters.status === 'all'
          ? 'Tất cả trạng thái'
          : this.currentFilters.status === 'hoat_dong'
            ? 'Hoạt động'
            : 'Vô hiệu hóa';

      filterSummary.textContent = `${roleText} - ${statusText}`;
    }
  }

  async loadUserData() {
    this.showLoading();

    try {
      let apiAction = '';
      let params = {};

      switch (this.currentReportType) {
        case 'summary':
          apiAction = 'get_user_summary';
          break;
        case 'growth':
          apiAction = 'get_user_growth';
          params = { months: 12 };
          break;
        case 'activity':
          apiAction = 'get_user_activity';
          params = { days: 30 };
          break;
        case 'role':
          apiAction = 'get_user_by_role';
          break;
        case 'status':
          apiAction = 'get_user_by_status';
          break;
        case 'segments':
          apiAction = 'get_user_segments';
          break;
        case 'top':
          apiAction = 'get_top_users';
          params = { limit: 10, type: 'revenue' };
          break;
        case 'recent':
          apiAction = 'get_recent_users';
          params = { limit: 10 };
          break;
      }

      const data = await this.fetchAPI('laydulieu_taikhoan.php', {
        action: apiAction,
        ...params,
      });

      this.hideLoading();

      if (data.success) {
        this.currentData = data;

        switch (this.currentReportType) {
          case 'summary':
            this.updateSummaryCards(data.data);
            this.renderRoleDistributionChart(data.data);
            this.renderStatusDistributionChart(data.data);
            break;
          case 'growth':
            this.renderUserGrowthChart(data.data);
            this.updateGrowthMetrics(data);
            break;
          case 'activity':
            this.renderUserActivityCharts(data.data);
            this.updateActivityMetrics(data);
            break;
          case 'role':
            this.renderRoleDetails(data.data);
            break;
          case 'status':
            this.renderStatusDetails(data.data);
            break;
          case 'segments':
            this.renderUserSegments(data.data);
            break;
          case 'top':
            this.renderTopUsers(data.data);
            break;
          case 'recent':
            this.renderRecentUsers(data.data);
            break;
        }
      } else {
        this.showToast(
          'Lỗi',
          data.message || 'Không thể tải dữ liệu người dùng',
          'error',
        );
      }
    } catch (error) {
      this.hideLoading();
      console.error('Error loading user data:', error);
      this.showToast('Lỗi', 'Không thể kết nối đến máy chủ', 'error');
    }
  }

  async loadUserSummary() {
    try {
      const data = await this.fetchAPI('laydulieu_taikhoan.php', {
        action: 'get_user_summary',
      });

      if (data.success) {
        this.updateSummaryStats(data.data);
      }
    } catch (error) {
      console.error('Error loading user summary:', error);
    }
  }

  async loadUserGrowth(months = 12) {
    try {
      const data = await this.fetchAPI('laydulieu_taikhoan.php', {
        action: 'get_user_growth',
        months: months,
      });

      if (data.success) {
        this.renderUserGrowthChart(data.data);
        this.updateGrowthMetrics(data);
      }
    } catch (error) {
      console.error('Error loading user growth:', error);
    }
  }

  async loadUserByRole() {
    this.currentReportType = 'role';
    document.getElementById('report-type').value = 'role';
    this.loadUserData();
  }

  async loadUserByStatus() {
    this.currentReportType = 'status';
    document.getElementById('report-type').value = 'status';
    this.loadUserData();
  }

  async loadUserSegments() {
    this.currentReportType = 'segments';
    document.getElementById('report-type').value = 'segments';
    this.loadUserData();
  }

  async loadTopUsers(type = 'revenue') {
    this.currentReportType = 'top';
    document.getElementById('report-type').value = 'top';

    try {
      const data = await this.fetchAPI('laydulieu_taikhoan.php', {
        action: 'get_top_users',
        limit: 10,
        type: type,
      });

      if (data.success) {
        this.renderTopUsers(data.data);
      }
    } catch (error) {
      console.error('Error loading top users:', error);
    }
  }

  async loadRecentUsers() {
    this.currentReportType = 'recent';
    document.getElementById('report-type').value = 'recent';
    this.loadUserData();
  }

  async loadLoginStatistics(days = 30) {
    try {
      const data = await this.fetchAPI('laydulieu_taikhoan.php', {
        action: 'get_login_statistics',
        days: days,
      });

      if (data.success) {
        this.renderLoginActivityChart(data.data);
        this.updateLoginMetrics(data);
      }
    } catch (error) {
      console.error('Error loading login statistics:', error);
    }
  }

  updateSummaryCards(data) {
    // Update summary cards
    document.getElementById('total-users').textContent =
      data.total_users?.toLocaleString() || '0';
    document.getElementById('new-users-today').textContent =
      `+${data.new_users_today?.toLocaleString() || '0'}`;
    document.getElementById('active-users').textContent =
      data.active_users?.toLocaleString() || '0';
    document.getElementById('new-users-month').textContent =
      data.new_users_month?.toLocaleString() || '0';
    document.getElementById('vip-users').textContent =
      data.vip_users?.toLocaleString() || '0';

    // Calculate rates
    const activeRate =
      data.total_users > 0
        ? Math.round((data.active_users / data.total_users) * 100)
        : 0;
    const vipRate =
      data.total_users > 0
        ? Math.round((data.vip_users / data.total_users) * 100)
        : 0;

    document.getElementById('active-rate').textContent = `${activeRate}%`;
    document.getElementById('vip-rate').textContent = `${vipRate}%`;
  }

  updateSummaryStats(data) {
    // Calculate growth rate
    const newUsersToday = data.new_users_today || 0;
    const newUsersMonth = data.new_users_month || 0;

    let growthRate = 0;
    if (newUsersMonth > 0) {
      // Simplified growth calculation
      const avgDailyNewUsers = newUsersMonth / 30; // Assuming 30 days in month
      growthRate =
        avgDailyNewUsers > 0
          ? Math.round((newUsersToday / avgDailyNewUsers - 1) * 100)
          : 0;
    }

    const growthElement = document.getElementById('growth-rate');
    if (growthElement) {
      growthElement.textContent = `${growthRate >= 0 ? '+' : ''}${growthRate}%`;

      if (growthRate > 0) {
        growthElement.className = 'font-medium trend-up';
      } else if (growthRate < 0) {
        growthElement.className = 'font-medium trend-down';
      } else {
        growthElement.className = 'font-medium trend-neutral';
      }
    }
  }

  updateGrowthMetrics(data) {
    if (!data || !data.summary) return;

    const summary = data.summary;

    document.getElementById('metric-daily-growth').textContent =
      summary.avg_new_users ? summary.avg_new_users.toFixed(1) : '0';

    document.getElementById('metric-weekly-growth').textContent =
      summary.avg_growth_rate ? `${summary.avg_growth_rate}%` : '0%';

    document.getElementById('metric-monthly-growth').textContent =
      summary.current_month_growth ? `${summary.current_month_growth}%` : '0%';

    // Calculate retention rate (simplified)
    const retentionRate = 65.5; // This would come from actual data
    document.getElementById('metric-retention-rate').textContent =
      `${retentionRate}%`;
  }

  updateActivityMetrics(data) {
    if (!data || !data.summary) return;

    const summary = data.summary;

    document.getElementById('avg-daily-active').textContent =
      summary.avg_daily_active_users
        ? summary.avg_daily_active_users.toFixed(1)
        : '0';
  }

  updateLoginMetrics(data) {
    if (!data || !data.summary) return;

    const summary = data.summary;

    document.getElementById('avg-daily-logins').textContent =
      summary.avg_daily_logins ? summary.avg_daily_logins.toFixed(1) : '0';

    document.getElementById('login-per-user').textContent =
      summary.avg_logins_per_user
        ? summary.avg_logins_per_user.toFixed(2)
        : '0';
  }

  renderRoleDistributionChart(data) {
    const ctx = document
      .getElementById('role-distribution-chart')
      ?.getContext('2d');
    if (!ctx) return;

    // Destroy existing chart
    if (this.roleDistributionChart) {
      this.roleDistributionChart.destroy();
    }

    const roleStats = data.role_stats || {};
    const roleLabels = data.role_labels || {};

    const labels = Object.keys(roleStats).map(
      (role) => roleLabels[role] || role,
    );
    const chartData = Object.values(roleStats);

    const colors = ['#3b82f6', '#f59e0b', '#8b5cf6']; // Blue, Orange, Purple

    this.roleDistributionChart = new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: labels,
        datasets: [
          {
            data: chartData,
            backgroundColor: colors,
            borderWidth: 2,
            borderColor: window.matchMedia('(prefers-color-scheme: dark)')
              .matches
              ? '#2a2015'
              : '#fff',
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'right',
            labels: {
              color: window.matchMedia('(prefers-color-scheme: dark)').matches
                ? '#fff'
                : '#333',
              font: {
                family: "'Plus Jakarta Sans', sans-serif",
                size: 11,
              },
              padding: 15,
            },
          },
          tooltip: {
            callbacks: {
              label: (context) => {
                const label = context.label || '';
                const value = context.raw || 0;
                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                const percentage = Math.round((value / total) * 100);
                return `${label}: ${value} người (${percentage}%)`;
              },
            },
          },
        },
        cutout: '70%',
      },
    });
  }

  renderStatusDistributionChart(data) {
    const ctx = document
      .getElementById('status-distribution-chart')
      ?.getContext('2d');
    if (!ctx) return;

    // Destroy existing chart
    if (this.statusDistributionChart) {
      this.statusDistributionChart.destroy();
    }

    const statusStats = data.status_stats || {};
    const statusLabels = data.status_labels || {};

    const labels = Object.keys(statusStats).map(
      (status) => statusLabels[status] || status,
    );
    const chartData = Object.values(statusStats);

    const colors = ['#10b981', '#6b7280']; // Green, Gray

    this.statusDistributionChart = new Chart(ctx, {
      type: 'pie',
      data: {
        labels: labels,
        datasets: [
          {
            data: chartData,
            backgroundColor: colors,
            borderWidth: 2,
            borderColor: window.matchMedia('(prefers-color-scheme: dark)')
              .matches
              ? '#2a2015'
              : '#fff',
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'right',
            labels: {
              color: window.matchMedia('(prefers-color-scheme: dark)').matches
                ? '#fff'
                : '#333',
              font: {
                family: "'Plus Jakarta Sans', sans-serif",
                size: 11,
              },
              padding: 15,
            },
          },
          tooltip: {
            callbacks: {
              label: (context) => {
                const label = context.label || '';
                const value = context.raw || 0;
                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                const percentage = Math.round((value / total) * 100);
                return `${label}: ${value} người (${percentage}%)`;
              },
            },
          },
        },
      },
    });
  }

  renderUserGrowthChart(data) {
    const ctx = document.getElementById('user-growth-chart')?.getContext('2d');
    if (!ctx) return;

    // Destroy existing chart
    if (this.userGrowthChart) {
      this.userGrowthChart.destroy();
    }

    const labels = data.map((item) => item.month_name);
    const newUsersData = data.map((item) => item.new_users);
    const totalUsersData = data.map((item) => item.total_users);
    const activeUsersData = data.map((item) => item.active_users);

    this.userGrowthChart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [
          {
            label: 'Người dùng mới',
            data: newUsersData,
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            borderWidth: 3,
            tension: 0.4,
            fill: true,
          },
          {
            label: 'Tổng người dùng',
            data: totalUsersData,
            borderColor: '#10b981',
            backgroundColor: 'transparent',
            borderWidth: 2,
            tension: 0.4,
            fill: false,
          },
          {
            label: 'Người dùng hoạt động',
            data: activeUsersData,
            borderColor: '#f59e0b',
            backgroundColor: 'transparent',
            borderWidth: 2,
            tension: 0.4,
            fill: false,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: true,
            position: 'top',
            labels: {
              color: window.matchMedia('(prefers-color-scheme: dark)').matches
                ? '#fff'
                : '#333',
            },
          },
          tooltip: {
            mode: 'index',
            intersect: false,
            callbacks: {
              label: (context) => {
                let label = context.dataset.label || '';
                label += ': ' + context.raw.toLocaleString();
                return label;
              },
            },
          },
        },
        scales: {
          x: {
            grid: {
              color: window.matchMedia('(prefers-color-scheme: dark)').matches
                ? 'rgba(255, 255, 255, 0.1)'
                : 'rgba(0, 0, 0, 0.1)',
            },
            ticks: {
              color: window.matchMedia('(prefers-color-scheme: dark)').matches
                ? '#aaa'
                : '#666',
              maxRotation: 45,
              minRotation: 45,
            },
          },
          y: {
            beginAtZero: true,
            grid: {
              color: window.matchMedia('(prefers-color-scheme: dark)').matches
                ? 'rgba(255, 255, 255, 0.1)'
                : 'rgba(0, 0, 0, 0.1)',
            },
            ticks: {
              color: window.matchMedia('(prefers-color-scheme: dark)').matches
                ? '#aaa'
                : '#666',
              callback: (value) => {
                return value.toLocaleString();
              },
            },
          },
        },
      },
    });
  }

  renderUserActivityCharts(data) {
    this.renderNewUsersChart(data);
    this.renderActiveUsersChart(data);
  }

  renderNewUsersChart(data) {
    const ctx = document.getElementById('new-users-chart')?.getContext('2d');
    if (!ctx) return;

    // Destroy existing chart
    if (this.newUsersChart) {
      this.newUsersChart.destroy();
    }

    const labels = data.map((item) => item.date_formatted);
    const newUsersData = data.map((item) => item.new_users);

    this.newUsersChart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [
          {
            label: 'Người dùng mới',
            data: newUsersData,
            backgroundColor: '#3b82f6',
            borderColor: '#3b82f6',
            borderWidth: 1,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false,
          },
          tooltip: {
            callbacks: {
              label: (context) => {
                return `Người dùng mới: ${context.raw}`;
              },
            },
          },
        },
        scales: {
          x: {
            grid: {
              display: false,
            },
            ticks: {
              color: window.matchMedia('(prefers-color-scheme: dark)').matches
                ? '#aaa'
                : '#666',
              maxRotation: 45,
              minRotation: 45,
            },
          },
          y: {
            beginAtZero: true,
            grid: {
              color: window.matchMedia('(prefers-color-scheme: dark)').matches
                ? 'rgba(255, 255, 255, 0.1)'
                : 'rgba(0, 0, 0, 0.1)',
            },
            ticks: {
              color: window.matchMedia('(prefers-color-scheme: dark)').matches
                ? '#aaa'
                : '#666',
              callback: (value) => {
                return value.toLocaleString();
              },
            },
          },
        },
      },
    });
  }

  renderActiveUsersChart(data) {
    const ctx = document.getElementById('active-users-chart')?.getContext('2d');
    if (!ctx) return;

    // Destroy existing chart
    if (this.activeUsersChart) {
      this.activeUsersChart.destroy();
    }

    const labels = data.map((item) => item.date_formatted);
    const activeUsersData = data.map((item) => item.active_users);

    this.activeUsersChart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [
          {
            label: 'Người dùng hoạt động',
            data: activeUsersData,
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            borderWidth: 3,
            tension: 0.4,
            fill: true,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false,
          },
          tooltip: {
            callbacks: {
              label: (context) => {
                return `Người dùng hoạt động: ${context.raw}`;
              },
            },
          },
        },
        scales: {
          x: {
            grid: {
              color: window.matchMedia('(prefers-color-scheme: dark)').matches
                ? 'rgba(255, 255, 255, 0.1)'
                : 'rgba(0, 0, 0, 0.1)',
            },
            ticks: {
              color: window.matchMedia('(prefers-color-scheme: dark)').matches
                ? '#aaa'
                : '#666',
              maxRotation: 45,
              minRotation: 45,
            },
          },
          y: {
            beginAtZero: true,
            grid: {
              color: window.matchMedia('(prefers-color-scheme: dark)').matches
                ? 'rgba(255, 255, 255, 0.1)'
                : 'rgba(0, 0, 0, 0.1)',
            },
            ticks: {
              color: window.matchMedia('(prefers-color-scheme: dark)').matches
                ? '#aaa'
                : '#666',
              callback: (value) => {
                return value.toLocaleString();
              },
            },
          },
        },
      },
    });
  }

  renderUserSegments(data) {
    this.renderSegmentsChart(data);
    this.renderSegmentsList(data);
    this.updateSegmentInsights(data);
  }

  renderSegmentsChart(data) {
    const ctx = document.getElementById('segments-chart')?.getContext('2d');
    if (!ctx) return;

    // Destroy existing chart
    if (this.segmentsChart) {
      this.segmentsChart.destroy();
    }

    const segments = Array.isArray(data) ? data : [];
    const labels = segments.map((segment) => segment.segment_name);
    const chartData = segments.map((segment) => segment.count);
    const colors = segments.map((segment) => segment.color || '#6b7280');

    this.segmentsChart = new Chart(ctx, {
      type: 'polarArea',
      data: {
        labels: labels,
        datasets: [
          {
            data: chartData,
            backgroundColor: colors,
            borderWidth: 2,
            borderColor: window.matchMedia('(prefers-color-scheme: dark)')
              .matches
              ? '#2a2015'
              : '#fff',
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'right',
            labels: {
              color: window.matchMedia('(prefers-color-scheme: dark)').matches
                ? '#fff'
                : '#333',
              font: {
                family: "'Plus Jakarta Sans', sans-serif",
                size: 11,
              },
              padding: 15,
            },
          },
          tooltip: {
            callbacks: {
              label: (context) => {
                const label = context.label || '';
                const value = context.raw || 0;
                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                const percentage = Math.round((value / total) * 100);
                return `${label}: ${value} người (${percentage}%)`;
              },
            },
          },
        },
      },
    });
  }

  renderSegmentsList(data) {
    const container = document.getElementById('segments-list');
    if (!container) return;

    const segments = Array.isArray(data) ? data : [];

    let html = '';
    segments.forEach((segment) => {
      html += `
                <div class="segment-card p-4 bg-[#f4ede7]/30 dark:bg-[#3d2e1f]/30 rounded-xl" style="border-left-color: ${segment.color}">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-3">
                            <div class="h-8 w-8 rounded-lg flex items-center justify-center text-white" style="background: ${segment.color}">
                                <span class="material-symbols-outlined text-sm">group</span>
                            </div>
                            <div>
                                <h4 class="font-bold">${segment.segment_name}</h4>
                                <p class="text-xs text-[#9c7349]">${segment.description}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-black">${segment.count.toLocaleString()}</div>
                            <div class="text-sm text-[#9c7349]">${segment.percentage}%</div>
                        </div>
                    </div>
                </div>
            `;
    });

    container.innerHTML = html;
  }

  updateSegmentInsights(data) {
    const segments = Array.isArray(data) ? data : [];

    // Find specific segments
    const newUsersSegment = segments.find((s) => s.segment === 'new_users');
    const vipSegment = segments.find((s) => s.segment === 'vip_users');
    const loyalSegment = segments.find((s) => s.segment === 'loyal_users');

    // Update insights
    if (newUsersSegment) {
      document.getElementById('segment-new-insight').textContent =
        `${newUsersSegment.count} người dùng mới, chiếm ${newUsersSegment.percentage}% tổng số khách hàng. Tập trung chuyển đổi họ thành người dùng thường xuyên.`;
    }

    if (vipSegment) {
      document.getElementById('segment-vip-insight').textContent =
        `${vipSegment.count} người dùng VIP, mang lại 80% doanh thu. Ưu tiên chăm sóc và có chính sách ưu đãi đặc biệt.`;
    }

    if (loyalSegment) {
      document.getElementById('segment-loyal-insight').textContent =
        `${loyalSegment.count} người dùng trung thành, tỷ lệ giữ chân cao nhất. Tiếp tục duy trì chất lượng dịch vụ để giữ chân nhóm này.`;
    }
  }

  renderRoleDetails(data) {
    // This would typically navigate to a detailed view
    // For now, just show a message
    this.showToast('Thông tin', 'Đã tải dữ liệu phân bố theo vai trò', 'info');
  }

  renderStatusDetails(data) {
    // This would typically navigate to a detailed view
    this.showToast(
      'Thông tin',
      'Đã tải dữ liệu phân bố theo trạng thái',
      'info',
    );
  }

  renderTopUsers(data) {
    const container = document.getElementById('top-revenue-users');
    if (!container) return;

    let html = '';
    data.forEach((user, index) => {
      const avatarText = user.full_name?.charAt(0) || 'U';

      html += `
                <div class="flex items-center justify-between p-3 bg-[#f4ede7]/30 dark:bg-[#3d2e1f]/30 rounded-xl">
                    <div class="flex items-center gap-3">
                        <div class="user-avatar">
                            ${avatarText}
                        </div>
                        <div>
                            <div class="font-medium">${user.full_name || 'Không tên'}</div>
                            <div class="text-xs text-[#9c7349]">${user.total_orders} đơn</div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="font-bold">${user.total_revenue_formatted}</div>
                        <div class="text-xs text-[#9c7349]">#${index + 1}</div>
                    </div>
                </div>
            `;
    });

    container.innerHTML = html;
  }

  renderRecentUsers(data) {
    const container = document.getElementById('recent-users-list');
    if (!container) return;

    let html = '';
    data.forEach((user) => {
      const avatarText = user.full_name?.charAt(0) || 'U';
      const roleClass =
        user.role === 'khach_hang'
          ? 'role-customer'
          : user.role === 'nhan_vien'
            ? 'role-staff'
            : 'role-admin';

      html += `
                <div class="flex items-center justify-between p-3 bg-[#f4ede7]/30 dark:bg-[#3d2e1f]/30 rounded-xl">
                    <div class="flex items-center gap-3">
                        <div class="user-avatar">
                            ${avatarText}
                        </div>
                        <div>
                            <div class="font-medium">${user.full_name || 'Không tên'}</div>
                            <div class="text-xs text-[#9c7349]">${user.registration_date_formatted}</div>
                        </div>
                    </div>
                    <div>
                        <span class="${roleClass} role-badge">
                            ${user.role_name}
                        </span>
                    </div>
                </div>
            `;
    });

    container.innerHTML = html;
  }

  renderLoginActivityChart(data) {
    const ctx = document
      .getElementById('login-activity-chart')
      ?.getContext('2d');
    if (!ctx) return;

    // Destroy existing chart
    if (this.loginActivityChart) {
      this.loginActivityChart.destroy();
    }

    const loginData = Array.isArray(data) ? data : [];
    const labels = loginData.map((item) => item.date_formatted);
    const loginCounts = loginData.map((item) => item.login_count);
    const uniqueUsers = loginData.map((item) => item.unique_users);

    this.loginActivityChart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [
          {
            label: 'Số lần đăng nhập',
            data: loginCounts,
            backgroundColor: '#3b82f6',
            borderColor: '#3b82f6',
            borderWidth: 1,
            order: 2,
          },
          {
            label: 'Người dùng đăng nhập',
            data: uniqueUsers,
            type: 'line',
            borderColor: '#10b981',
            backgroundColor: 'transparent',
            borderWidth: 3,
            tension: 0.4,
            order: 1,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: true,
            position: 'top',
            labels: {
              color: window.matchMedia('(prefers-color-scheme: dark)').matches
                ? '#fff'
                : '#333',
            },
          },
          tooltip: {
            mode: 'index',
            intersect: false,
            callbacks: {
              label: (context) => {
                let label = context.dataset.label || '';
                label += ': ' + context.raw.toLocaleString();
                return label;
              },
            },
          },
        },
        scales: {
          x: {
            grid: {
              display: false,
            },
            ticks: {
              color: window.matchMedia('(prefers-color-scheme: dark)').matches
                ? '#aaa'
                : '#666',
              maxRotation: 45,
              minRotation: 45,
            },
          },
          y: {
            beginAtZero: true,
            grid: {
              color: window.matchMedia('(prefers-color-scheme: dark)').matches
                ? 'rgba(255, 255, 255, 0.1)'
                : 'rgba(0, 0, 0, 0.1)',
            },
            ticks: {
              color: window.matchMedia('(prefers-color-scheme: dark)').matches
                ? '#aaa'
                : '#666',
              callback: (value) => {
                return value.toLocaleString();
              },
            },
          },
        },
      },
    });
  }

  switchTab(tabId) {
    // Update active tab button
    document.querySelectorAll('.tab-button').forEach((btn) => {
      btn.classList.remove('active');
      if (btn.dataset.tab === tabId) {
        btn.classList.add('active');
      }
    });

    // Update active tab content
    document.querySelectorAll('.tab-content').forEach((content) => {
      content.classList.add('hidden');
    });

    const activeContent = document.getElementById(`${tabId}-tab`);
    if (activeContent) {
      activeContent.classList.remove('hidden');
    }

    // Load specific data for the tab if needed
    switch (tabId) {
      case 'growth':
        this.loadUserGrowth(12);
        break;
      case 'activity':
        this.loadUserActivity(30);
        break;
      case 'segments':
        this.loadUserSegments();
        break;
      case 'login':
        this.loadLoginStatistics(30);
        break;
    }
  }

  async loadUserActivity(days = 30) {
    try {
      const data = await this.fetchAPI('laydulieu_taikhoan.php', {
        action: 'get_user_activity',
        days: days,
      });

      if (data.success) {
        this.renderUserActivityCharts(data.data);
        this.updateActivityMetrics(data);
      }
    } catch (error) {
      console.error('Error loading user activity:', error);
    }
  }

  prevPage() {
    if (this.currentPage > 1) {
      this.currentPage--;
      this.renderTablePage();
      this.updatePaginationUI();
    }
  }

  nextPage() {
    const totalPages = Math.ceil(this.totalItems / this.itemsPerPage);
    if (this.currentPage < totalPages) {
      this.currentPage++;
      this.renderTablePage();
      this.updatePaginationUI();
    }
  }

  goToPage(page) {
    const totalPages = Math.ceil(this.totalItems / this.itemsPerPage);
    if (page >= 1 && page <= totalPages) {
      this.currentPage = page;
      this.renderTablePage();
      this.updatePaginationUI();
    }
  }

  updatePaginationUI() {
    const totalPages = Math.ceil(this.totalItems / this.itemsPerPage);

    const currentPageInput = document.getElementById('current-page');
    const totalPagesSpan = document.getElementById('total-pages');
    const prevBtn = document.getElementById('prev-page');
    const nextBtn = document.getElementById('next-page');

    if (currentPageInput) {
      currentPageInput.value = this.currentPage;
      currentPageInput.max = totalPages;
    }

    if (totalPagesSpan) {
      totalPagesSpan.textContent = totalPages;
    }

    if (prevBtn) {
      prevBtn.disabled = this.currentPage === 1;
    }

    if (nextBtn) {
      nextBtn.disabled = this.currentPage === totalPages;
    }
  }

  renderTablePage() {
    const start = (this.currentPage - 1) * this.itemsPerPage;
    const end = start + this.itemsPerPage;
    const pageData = this.allUsers.slice(start, end);

    const tableBody = document.getElementById('users-table-body');
    if (!tableBody) return;

    let html = '';
    pageData.forEach((user, index) => {
      const globalIndex = start + index + 1;
      const roleClass = this.getRoleClass(user.role);
      const statusClass = this.getStatusClass(user.status);

      html += `
                <tr class="border-b border-[#f4ede7] dark:border-[#3d2e1f] hover:bg-[#f4ede7]/30 dark:hover:bg-[#3d2e1f]/30">
                    <td class="px-4 py-3 text-sm">${globalIndex}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="user-avatar">
                                ${user.full_name?.charAt(0) || 'U'}
                            </div>
                            <div>
                                <div class="font-medium">${user.full_name || 'Không tên'}</div>
                                <div class="text-xs text-[#9c7349]">${user.email || ''}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <span class="${roleClass} role-badge">
                            ${user.role_name || user.role}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="${statusClass} status-badge">
                            ${user.status_name || user.status}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm">${user.total_orders || 0}</td>
                    <td class="px-4 py-3 text-sm font-medium">${user.total_revenue_formatted || '0₫'}</td>
                    <td class="px-4 py-3 text-sm text-[#9c7349]">${user.registration_date_formatted || ''}</td>
                    <td class="px-4 py-3">
                        <button class="p-2 hover:bg-[#f4ede7] dark:hover:bg-[#3d2e1f] rounded-lg" onclick="userReport.viewUserDetail('${user.user_id}')">
                            <span class="material-symbols-outlined text-lg">visibility</span>
                        </button>
                    </td>
                </tr>
            `;
    });

    tableBody.innerHTML = html;
  }

  getRoleClass(role) {
    switch (role) {
      case 'khach_hang':
        return 'role-customer';
      case 'nhan_vien':
        return 'role-staff';
      case 'quan_tri':
        return 'role-admin';
      default:
        return 'role-customer';
    }
  }

  getStatusClass(status) {
    switch (status) {
      case 'hoat_dong':
        return 'status-active';
      case 'vo_hieu_hoa':
        return 'status-inactive';
      case 'khoa':
        return 'status-locked';
      default:
        return 'status-active';
    }
  }

  filterUsers(searchTerm) {
    if (!searchTerm) {
      this.allUsers = this.originalUsers || [];
    } else {
      const term = searchTerm.toLowerCase();
      this.allUsers = (this.originalUsers || []).filter(
        (user) =>
          (user.full_name?.toLowerCase() || '').includes(term) ||
          (user.email?.toLowerCase() || '').includes(term) ||
          (user.phone?.toLowerCase() || '').includes(term),
      );
    }

    this.totalItems = this.allUsers.length;
    this.currentPage = 1;
    this.renderTablePage();
    this.updatePaginationUI();
  }

  sortUsers(sortBy) {
    if (!this.allUsers || this.allUsers.length === 0) return;

    switch (sortBy) {
      case 'name_asc':
        this.allUsers.sort((a, b) =>
          (a.full_name || '').localeCompare(b.full_name || ''),
        );
        break;
      case 'name_desc':
        this.allUsers.sort((a, b) =>
          (b.full_name || '').localeCompare(a.full_name || ''),
        );
        break;
      case 'date_asc':
        this.allUsers.sort(
          (a, b) =>
            new Date(a.registration_date) - new Date(b.registration_date),
        );
        break;
      case 'date_desc':
        this.allUsers.sort(
          (a, b) =>
            new Date(b.registration_date) - new Date(a.registration_date),
        );
        break;
      case 'orders_desc':
        this.allUsers.sort(
          (a, b) => (b.total_orders || 0) - (a.total_orders || 0),
        );
        break;
      case 'revenue_desc':
        this.allUsers.sort(
          (a, b) => (b.total_revenue || 0) - (a.total_revenue || 0),
        );
        break;
    }

    this.renderTablePage();
  }

  openExportModal() {
    const modal = document.getElementById('export-modal');
    if (modal) {
      modal.classList.remove('hidden');
      modal.style.display = 'flex';
    }
  }

  closeExportModal() {
    const modal = document.getElementById('export-modal');
    if (modal) {
      modal.classList.add('hidden');
      modal.style.display = 'none';
    }
  }

  exportUsers() {
    const format =
      document.querySelector('input[name="export-format"]:checked')?.value ||
      'csv';

    switch (format) {
      case 'csv':
        this.exportToCSV();
        break;
      case 'excel':
        this.exportToExcel();
        break;
      case 'pdf':
        this.exportToPDF();
        break;
    }

    this.closeExportModal();
  }

  exportToCSV() {
    const headers = [
      'STT',
      'Họ tên',
      'Email',
      'SĐT',
      'Vai trò',
      'Trạng thái',
      'Số đơn',
      'Tổng chi tiêu',
      'Ngày đăng ký',
    ];
    const rows = this.allUsers.map((user, index) => [
      index + 1,
      user.full_name || '',
      user.email || '',
      user.phone || '',
      user.role_name || user.role || '',
      user.status_name || user.status || '',
      user.total_orders || 0,
      user.total_revenue || 0,
      user.registration_date_formatted || '',
    ]);

    const csvContent = [
      headers.join(','),
      ...rows.map((row) => row.map((cell) => `"${cell}"`).join(',')),
    ].join('\n');

    const BOM = '\uFEFF';
    const blob = new Blob([BOM + csvContent], {
      type: 'text/csv;charset=utf-8;',
    });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);

    link.setAttribute('href', url);
    link.setAttribute(
      'download',
      `users_report_${new Date().toISOString().split('T')[0]}.csv`,
    );
    link.style.visibility = 'hidden';

    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    this.showToast(
      'Thành công',
      'Đã xuất dữ liệu người dùng sang CSV',
      'success',
    );
  }

  exportToExcel() {
    // For Excel export, you would typically use a library like SheetJS
    // For now, just export as CSV with .xlsx extension
    this.showToast(
      'Thông báo',
      'Chức năng xuất Excel đang được phát triển. Vui lòng sử dụng xuất CSV.',
      'info',
    );
  }

  exportToPDF() {
    // For PDF export, you would typically use a library like jsPDF
    this.showToast(
      'Thông báo',
      'Chức năng xuất PDF đang được phát triển. Vui lòng sử dụng xuất CSV.',
      'info',
    );
  }

  viewUserDetail(userId) {
    // Open user detail modal or navigate to detail page
    this.showToast('Thông tin', `Xem chi tiết người dùng ${userId}`, 'info');
    // You can implement detailed view here
  }

  refreshData() {
    this.showLoading();
    this.loadUserSummary();
    this.loadUserData();
    this.showToast('Thành công', 'Đã làm mới dữ liệu báo cáo', 'success');
  }

  showLoading() {
    const loadingOverlay = document.getElementById('loadingOverlay');
    if (loadingOverlay) {
      loadingOverlay.style.display = 'flex';
    }
  }

  hideLoading() {
    const loadingOverlay = document.getElementById('loadingOverlay');
    if (loadingOverlay) {
      loadingOverlay.style.display = 'none';
    }
  }

  showToast(title, message, type = 'info') {
    const toast = document.createElement('div');
    const bgColor =
      {
        success: 'bg-green-500',
        error: 'bg-red-500',
        warning: 'bg-yellow-500',
        info: 'bg-blue-500',
      }[type] || 'bg-blue-500';

    toast.className = `fixed top-4 right-4 ${bgColor} text-white px-6 py-4 rounded-2xl shadow-2xl z-[9999] animate-slide-in`;
    toast.innerHTML = `
            <div class="flex items-start gap-3">
                <span class="material-symbols-outlined">
                    ${type === 'success' ? 'check_circle' : type === 'error' ? 'error' : type === 'warning' ? 'warning' : 'info'}
                </span>
                <div>
                    <div class="font-bold text-sm">${title}</div>
                    <div class="text-xs opacity-90 mt-1">${message}</div>
                </div>
            </div>
        `;

    document.body.appendChild(toast);

    setTimeout(() => {
      toast.style.animation = 'slide-out 0.3s ease-out';
      setTimeout(() => {
        toast.remove();
      }, 300);
    }, 3000);
  }

  async fetchAPI(endpoint, params = {}) {
    const url = new URL(
      `${this.API_BASE_URL}/${endpoint}`,
      window.location.origin,
    );
    Object.keys(params).forEach((key) =>
      url.searchParams.append(key, params[key]),
    );

    const response = await fetch(url);
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    return await response.json();
  }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  window.userReport = new UserReport();
});

// Add animation styles
const style = document.createElement('style');
style.textContent = `
    @keyframes slide-in {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slide-out {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    .animate-slide-in {
        animation: slide-in 0.3s ease-out;
    }
`;
document.head.appendChild(style);

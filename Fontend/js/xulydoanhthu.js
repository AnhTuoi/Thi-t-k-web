// Fontend/js/xulydoanhthu.js
class RevenueReport {
  constructor() {
    this.API_BASE_URL = '../../api';
    this.currentData = null;
    this.currentReportType = 'daily';
    this.currentFilters = {
      startDate: new Date().toISOString().split('T')[0],
      endDate: new Date().toISOString().split('T')[0],
      category: 'all',
      limit: 10,
      reportType: 'daily',
    };

    // Chart instances
    this.revenueChart = null;
    this.categoryChart = null;
    this.paymentChart = null;
    this.comparisonChart = null;

    // Table pagination
    this.currentPage = 1;
    this.itemsPerPage = 10;
    this.totalItems = 0;

    this.init();
  }

  init() {
    this.setupEventListeners();
    this.setDefaultDates();
    this.loadRevenueSummary();
    this.loadRevenueData();
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

    // Quick date buttons
    document.querySelectorAll('.quick-date-btn').forEach((btn) => {
      btn.addEventListener('click', (e) => {
        const days = parseInt(e.target.dataset.days);
        this.setQuickDateRange(days);
      });
    });

    // Report type change
    document.getElementById('report-type')?.addEventListener('change', (e) => {
      this.currentReportType = e.target.value;
      this.updateFilterVisibility();
      this.loadRevenueData();
    });

    // Tab navigation
    document.querySelectorAll('.tab-button').forEach((tab) => {
      tab.addEventListener('click', (e) => {
        const tabId = e.target.dataset.tab;
        this.switchTab(tabId);
      });
    });

    // Chart type change
    document.getElementById('chart-type')?.addEventListener('change', (e) => {
      this.updateChartType(e.target.value);
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

    // Table search
    document.getElementById('table-search')?.addEventListener('input', (e) => {
      this.filterTable(e.target.value);
    });

    // Export buttons
    document
      .getElementById('export-btn')
      ?.addEventListener('click', () => this.openExportModal());
    document
      .getElementById('export-table')
      ?.addEventListener('click', () => this.exportTable());
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

    // Download chart
    document
      .getElementById('download-chart')
      ?.addEventListener('click', () => this.downloadChart());

    // Comparison period change
    document
      .getElementById('comparison-period')
      ?.addEventListener('change', () => {
        this.loadComparisonData();
      });
  }

  setDefaultDates() {
    const today = new Date();
    const thirtyDaysAgo = new Date();
    thirtyDaysAgo.setDate(today.getDate() - 30);

    const startDateInput = document.getElementById('start-date');
    const endDateInput = document.getElementById('end-date');

    if (startDateInput && endDateInput) {
      startDateInput.value = thirtyDaysAgo.toISOString().split('T')[0];
      endDateInput.value = today.toISOString().split('T')[0];

      this.currentFilters.startDate = startDateInput.value;
      this.currentFilters.endDate = endDateInput.value;
    }
  }

  setQuickDateRange(days) {
    const endDate = new Date();
    const startDate = new Date();
    startDate.setDate(endDate.getDate() - days + 1);

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

  updateFilterVisibility() {
    const categoryContainer = document.getElementById(
      'category-filter-container',
    );
    const reportType = this.currentReportType;

    if (categoryContainer) {
      if (reportType === 'category' || reportType === 'food') {
        categoryContainer.style.display = 'block';
      } else {
        categoryContainer.style.display = 'none';
      }
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
    const categoryFilter = document.getElementById('category-filter');
    const limitResults = document.getElementById('limit-results');
    const reportType = document.getElementById('report-type');

    if (startDateInput && endDateInput) {
      this.currentFilters.startDate = startDateInput.value;
      this.currentFilters.endDate = endDateInput.value;
    }

    if (categoryFilter) {
      this.currentFilters.category = categoryFilter.value;
    }

    if (limitResults) {
      this.currentFilters.limit = parseInt(limitResults.value);
    }

    if (reportType) {
      this.currentFilters.reportType = reportType.value;
      this.currentReportType = reportType.value;
    }

    // Update filter summary
    this.updateFilterSummary();

    // Load data with new filters
    this.loadRevenueData();

    this.showToast(
      'Đã áp dụng bộ lọc',
      'Dữ liệu đang được làm mới...',
      'success',
    );
  }

  resetFilters() {
    this.setDefaultDates();

    const categoryFilter = document.getElementById('category-filter');
    const limitResults = document.getElementById('limit-results');
    const reportType = document.getElementById('report-type');

    if (categoryFilter) categoryFilter.value = 'all';
    if (limitResults) limitResults.value = '10';
    if (reportType) reportType.value = 'daily';

    this.currentFilters = {
      startDate:
        document.getElementById('start-date')?.value ||
        new Date().toISOString().split('T')[0],
      endDate:
        document.getElementById('end-date')?.value ||
        new Date().toISOString().split('T')[0],
      category: 'all',
      limit: 10,
      reportType: 'daily',
    };

    this.currentReportType = 'daily';
    this.updateFilterVisibility();
    this.updateFilterSummary();
    this.loadRevenueData();

    this.showToast(
      'Đã đặt lại bộ lọc',
      'Tất cả bộ lọc đã được đặt về mặc định',
      'success',
    );
  }

  updateFilterSummary() {
    const filterSummary = document.getElementById('filter-summary');
    if (filterSummary) {
      const startDate = new Date(this.currentFilters.startDate);
      const endDate = new Date(this.currentFilters.endDate);

      const startFormatted = startDate.toLocaleDateString('vi-VN');
      const endFormatted = endDate.toLocaleDateString('vi-VN');

      let reportTypeText = '';
      switch (this.currentReportType) {
        case 'daily':
          reportTypeText = 'Theo ngày';
          break;
        case 'monthly':
          reportTypeText = 'Theo tháng';
          break;
        case 'yearly':
          reportTypeText = 'Theo năm';
          break;
        case 'category':
          reportTypeText = 'Theo danh mục';
          break;
        case 'food':
          reportTypeText = 'Theo món ăn';
          break;
        case 'payment':
          reportTypeText = 'Theo phương thức TT';
          break;
        case 'time':
          reportTypeText = 'Theo khung giờ';
          break;
      }

      filterSummary.textContent = `${startFormatted} đến ${endFormatted} - ${reportTypeText}`;
    }
  }

  async loadRevenueData() {
    this.showLoading();

    try {
      let apiAction = '';
      let params = {
        start_date: this.currentFilters.startDate,
        end_date: this.currentFilters.endDate,
      };

      switch (this.currentReportType) {
        case 'daily':
          apiAction = 'get_revenue_daily';
          break;
        case 'monthly':
          apiAction = 'get_revenue_monthly';
          params = { year: new Date().getFullYear() };
          break;
        case 'yearly':
          apiAction = 'get_revenue_yearly';
          params = { years: 5 };
          break;
        case 'category':
          apiAction = 'get_revenue_by_category';
          break;
        case 'food':
          apiAction = 'get_revenue_by_food';
          params.limit = this.currentFilters.limit;
          break;
        case 'payment':
          apiAction = 'get_revenue_by_payment_method';
          break;
        case 'time':
          apiAction = 'get_revenue_by_time_period';
          break;
      }

      const data = await this.fetchAPI('laydulieu_doanhthu.php', {
        action: apiAction,
        ...params,
      });

      this.hideLoading();

      if (data.success) {
        this.currentData = data;
        this.updateSummaryCards(data);
        this.renderChart(data);
        this.renderTable(data);
        this.updateAnalysis(data);
      } else {
        this.showToast(
          'Lỗi',
          data.message || 'Không thể tải dữ liệu doanh thu',
          'error',
        );
      }
    } catch (error) {
      this.hideLoading();
      console.error('Error loading revenue data:', error);
      this.showToast('Lỗi', 'Không thể kết nối đến máy chủ', 'error');
    }
  }

  async loadRevenueSummary() {
    try {
      const data = await this.fetchAPI('laydulieu_doanhthu.php', {
        action: 'get_revenue_summary',
      });

      if (data.success) {
        this.updateSummaryStats(data.data);
      }
    } catch (error) {
      console.error('Error loading revenue summary:', error);
    }
  }

  async loadRevenueByCategory() {
    this.currentReportType = 'category';
    document.getElementById('report-type').value = 'category';
    this.updateFilterVisibility();
    this.loadRevenueData();
  }

  async loadRevenueByPayment() {
    this.currentReportType = 'payment';
    document.getElementById('report-type').value = 'payment';
    this.updateFilterVisibility();
    this.loadRevenueData();
  }

  async loadComparisonData() {
    const period =
      document.getElementById('comparison-period')?.value || 'month';

    try {
      const data = await this.fetchAPI('laydulieu_doanhthu.php', {
        action: 'get_revenue_comparison',
        period: period,
      });

      if (data.success) {
        this.renderComparisonChart(data.data);
        this.updateComparisonStats(data.data);
      }
    } catch (error) {
      console.error('Error loading comparison data:', error);
    }
  }

  updateSummaryCards(data) {
    if (!data || !data.summary) return;

    const summary = data.summary;

    // Update main summary cards
    if (summary.total_revenue_formatted) {
      document.getElementById('total-revenue').textContent =
        summary.total_revenue_formatted;
    }

    if (summary.total_orders !== undefined) {
      document.getElementById('total-orders').textContent =
        summary.total_orders.toLocaleString();
    }

    if (summary.avg_daily_revenue !== undefined) {
      const avgDaily = summary.avg_daily_revenue;
      document.getElementById('avg-daily-revenue').textContent =
        avgDaily.toLocaleString('vi-VN', {
          minimumFractionDigits: 0,
          maximumFractionDigits: 0,
        }) + 'đ';
    }

    if (summary.date_range) {
      const range = summary.date_range;
      document.getElementById('period-range').textContent =
        `${range.start_date_formatted} - ${range.end_date_formatted}`;
    }

    // Update additional stats
    if (data.data && data.data.length > 0) {
      // Calculate average order value
      const totalRevenue = summary.total_revenue || 0;
      const totalOrders = summary.total_orders || 0;
      const avgOrderValue = totalOrders > 0 ? totalRevenue / totalOrders : 0;

      document.getElementById('avg-order-value').textContent =
        avgOrderValue.toLocaleString('vi-VN', {
          minimumFractionDigits: 0,
          maximumFractionDigits: 0,
        }) + 'đ';

      // Calculate unique customers if available
      if (data.data[0].unique_customers !== undefined) {
        const uniqueCustomers = data.data.reduce(
          (sum, item) => sum + (item.unique_customers || 0),
          0,
        );
        document.getElementById('unique-customers').textContent =
          uniqueCustomers.toLocaleString();

        const ordersPerCustomer =
          totalOrders > 0 ? (totalOrders / uniqueCustomers).toFixed(1) : 0;
        document.getElementById('orders-per-customer').textContent =
          ordersPerCustomer;
      }
    }
  }

  updateSummaryStats(data) {
    // Today vs Yesterday growth
    if (data.today && data.yesterday) {
      const todayRevenue = data.today.total_revenue;
      const yesterdayRevenue = data.yesterday.total_revenue;

      let growth = 0;
      if (yesterdayRevenue > 0) {
        growth = ((todayRevenue - yesterdayRevenue) / yesterdayRevenue) * 100;
      } else if (todayRevenue > 0) {
        growth = 100;
      }

      const growthElement = document.getElementById('revenue-growth');
      if (growthElement) {
        growthElement.textContent = `${growth >= 0 ? '+' : ''}${growth.toFixed(1)}%`;

        if (growth > 0) {
          growthElement.className = 'font-medium trend-up';
        } else if (growth < 0) {
          growthElement.className = 'font-medium trend-down';
        } else {
          growthElement.className = 'font-medium trend-neutral';
        }
      }
    }
  }

  renderChart(data) {
    if (!data || !data.data) return;

    const ctx = document.getElementById('revenue-chart').getContext('2d');

    // Destroy existing chart
    if (this.revenueChart) {
      this.revenueChart.destroy();
    }

    let labels = [];
    let chartData = [];
    let label = 'Doanh thu';

    switch (this.currentReportType) {
      case 'daily':
        labels = data.data.map((item) => item.date_formatted);
        chartData = data.data.map((item) => item.total_revenue);
        label = 'Doanh thu theo ngày (VND)';
        document.getElementById('chart-title').textContent =
          'Doanh thu theo ngày';
        break;

      case 'monthly':
        labels = data.data.map((item) => item.month_name);
        chartData = data.data.map((item) => item.total_revenue);
        label = 'Doanh thu theo tháng (VND)';
        document.getElementById('chart-title').textContent =
          'Doanh thu theo tháng';
        break;

      case 'yearly':
        labels = data.data.map((item) => item.year.toString());
        chartData = data.data.map((item) => item.total_revenue);
        label = 'Doanh thu theo năm (VND)';
        document.getElementById('chart-title').textContent =
          'Doanh thu theo năm';
        break;

      case 'category':
        labels = data.data.map((item) => item.category_name);
        chartData = data.data.map((item) => item.total_revenue);
        label = 'Doanh thu theo danh mục (VND)';
        document.getElementById('chart-title').textContent =
          'Doanh thu theo danh mục';
        break;

      case 'food':
        labels = data.data.map((item) => item.food_name);
        chartData = data.data.map((item) => item.total_revenue);
        label = 'Doanh thu theo món ăn (VND)';
        document.getElementById('chart-title').textContent =
          'Top món ăn theo doanh thu';
        break;

      case 'payment':
        labels = data.data.map((item) => item.payment_method_name);
        chartData = data.data.map((item) => item.total_revenue);
        label = 'Doanh thu theo phương thức TT (VND)';
        document.getElementById('chart-title').textContent =
          'Doanh thu theo phương thức thanh toán';
        break;

      case 'time':
        labels = data.data.map((item) => item.hour_formatted);
        chartData = data.data.map((item) => item.total_revenue);
        label = 'Doanh thu theo khung giờ (VND)';
        document.getElementById('chart-title').textContent =
          'Doanh thu theo khung giờ trong ngày';
        break;
    }

    const chartType = document.getElementById('chart-type')?.value || 'line';

    this.revenueChart = new Chart(ctx, {
      type: chartType,
      data: {
        labels: labels,
        datasets: [
          {
            label: label,
            data: chartData,
            borderColor: '#f48c25',
            backgroundColor:
              chartType === 'line' || chartType === 'area'
                ? 'rgba(244, 140, 37, 0.1)'
                : '#f48c25',
            borderWidth: chartType === 'line' ? 3 : 2,
            fill: chartType === 'area',
            tension: 0.4,
            pointBackgroundColor: '#f48c25',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 4,
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
              font: {
                family: "'Plus Jakarta Sans', sans-serif",
              },
            },
          },
          tooltip: {
            mode: 'index',
            intersect: false,
            callbacks: {
              label: (context) => {
                let label = context.dataset.label || '';
                if (label) {
                  label += ': ';
                }
                label += context.raw.toLocaleString('vi-VN') + 'đ';
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
              font: {
                family: "'Plus Jakarta Sans', sans-serif",
              },
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
              font: {
                family: "'Plus Jakarta Sans', sans-serif",
              },
              callback: (value) => {
                return value.toLocaleString('vi-VN') + 'đ';
              },
            },
          },
        },
      },
    });

    // Also render category and payment charts
    this.renderSecondaryCharts();
  }

  async renderSecondaryCharts() {
    // Load category chart
    try {
      const categoryData = await this.fetchAPI('laydulieu_doanhthu.php', {
        action: 'get_revenue_by_category',
        start_date: this.currentFilters.startDate,
        end_date: this.currentFilters.endDate,
      });

      if (categoryData.success) {
        this.renderCategoryChart(categoryData.data);
      }
    } catch (error) {
      console.error('Error loading category chart:', error);
    }

    // Load payment chart
    try {
      const paymentData = await this.fetchAPI('laydulieu_doanhthu.php', {
        action: 'get_revenue_by_payment_method',
        start_date: this.currentFilters.startDate,
        end_date: this.currentFilters.endDate,
      });

      if (paymentData.success) {
        this.renderPaymentChart(paymentData.data);
      }
    } catch (error) {
      console.error('Error loading payment chart:', error);
    }
  }

  renderCategoryChart(data) {
    const ctx = document.getElementById('category-chart')?.getContext('2d');
    if (!ctx) return;

    if (this.categoryChart) {
      this.categoryChart.destroy();
    }

    const labels = data.map((item) => item.category_name);
    const chartData = data.map((item) => item.total_revenue);

    const colors = [
      '#f48c25',
      '#3b82f6',
      '#10b981',
      '#8b5cf6',
      '#ef4444',
      '#f59e0b',
    ];

    this.categoryChart = new Chart(ctx, {
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
            display: false,
          },
          tooltip: {
            callbacks: {
              label: (context) => {
                const label = context.label || '';
                const value = context.raw || 0;
                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                const percentage = Math.round((value / total) * 100);
                return `${label}: ${value.toLocaleString('vi-VN')}đ (${percentage}%)`;
              },
            },
          },
        },
        cutout: '70%',
      },
    });
  }

  renderPaymentChart(data) {
    const ctx = document.getElementById('payment-chart')?.getContext('2d');
    if (!ctx) return;

    if (this.paymentChart) {
      this.paymentChart.destroy();
    }

    const labels = data.map((item) => item.payment_method_name);
    const chartData = data.map((item) => item.total_revenue);

    const colors = ['#10b981', '#3b82f6', '#f59e0b'];

    this.paymentChart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [
          {
            label: 'Doanh thu',
            data: chartData,
            backgroundColor: colors,
            borderColor: colors.map((color) => color + 'CC'),
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
                return `${context.label}: ${context.raw.toLocaleString('vi-VN')}đ`;
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
                return value.toLocaleString('vi-VN') + 'đ';
              },
            },
          },
        },
      },
    });
  }

  renderComparisonChart(data) {
    const ctx = document.getElementById('comparison-chart')?.getContext('2d');
    if (!ctx) return;

    if (this.comparisonChart) {
      this.comparisonChart.destroy();
    }

    const currentPeriod = data.current_period;
    const comparisonPeriod = data.comparison_period;

    this.comparisonChart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: [currentPeriod.period, comparisonPeriod.period],
        datasets: [
          {
            label: 'Doanh thu',
            data: [currentPeriod.total_revenue, comparisonPeriod.total_revenue],
            backgroundColor: ['#f48c25', '#3b82f6'],
            borderColor: ['#f48c25CC', '#3b82f6CC'],
            borderWidth: 1,
          },
          {
            label: 'Số đơn hàng',
            data: [currentPeriod.order_count, comparisonPeriod.order_count],
            backgroundColor: ['#10b981', '#8b5cf6'],
            borderColor: ['#10b981CC', '#8b5cf6CC'],
            borderWidth: 1,
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
            callbacks: {
              label: (context) => {
                let label = context.dataset.label || '';
                if (context.datasetIndex === 0) {
                  label += ': ' + context.raw.toLocaleString('vi-VN') + 'đ';
                } else {
                  label += ': ' + context.raw.toLocaleString('vi-VN') + ' đơn';
                }
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
                return value.toLocaleString('vi-VN');
              },
            },
          },
        },
      },
    });
  }

  renderTable(data) {
    if (!data || !data.data) return;

    const tableHeader = document.getElementById('table-header');
    const tableBody = document.getElementById('table-body');

    if (!tableHeader || !tableBody) return;

    this.totalItems = data.data.length;
    this.updateTablePagination();

    // Determine headers based on report type
    let headers = [];
    switch (this.currentReportType) {
      case 'daily':
        headers = ['Ngày', 'Số đơn', 'Doanh thu', 'Đơn TB', 'Khách hàng'];
        break;
      case 'monthly':
        headers = ['Tháng', 'Số đơn', 'Doanh thu', 'Đơn TB', 'Khách hàng'];
        break;
      case 'yearly':
        headers = ['Năm', 'Số đơn', 'Doanh thu', 'Đơn TB', 'Khách hàng'];
        break;
      case 'category':
        headers = ['Danh mục', 'Số đơn', 'Doanh thu', 'Số lượng', 'Tỷ lệ'];
        break;
      case 'food':
        headers = [
          'Món ăn',
          'Danh mục',
          'Đã bán',
          'Doanh thu',
          'Đánh giá',
          'Tỷ lệ',
        ];
        break;
      case 'payment':
        headers = ['Phương thức', 'Số đơn', 'Doanh thu', 'Đơn TB', 'Tỷ lệ'];
        break;
      case 'time':
        headers = [
          'Khung giờ',
          'Số đơn',
          'Doanh thu',
          'Đơn/giờ',
          'Doanh thu/giờ',
        ];
        break;
    }

    // Update table header
    tableHeader.innerHTML = `
            <tr class="border-b border-[#f4ede7] dark:border-[#3d2e1f]">
                ${headers.map((header) => `<th class="text-left py-3 px-4 font-medium">${header}</th>`).join('')}
            </tr>
        `;

    // Calculate pagination
    const startIndex = (this.currentPage - 1) * this.itemsPerPage;
    const endIndex = Math.min(startIndex + this.itemsPerPage, this.totalItems);
    const paginatedData = data.data.slice(startIndex, endIndex);

    // Update table body
    let tableRows = '';

    paginatedData.forEach((item, index) => {
      let row = '';

      switch (this.currentReportType) {
        case 'daily':
          row = `
                        <td class="py-3 px-4">${item.date_formatted}</td>
                        <td class="py-3 px-4">${item.order_count.toLocaleString()}</td>
                        <td class="py-3 px-4 font-bold">${item.total_revenue_formatted}</td>
                        <td class="py-3 px-4">${item.avg_order_value_formatted}</td>
                        <td class="py-3 px-4">${item.unique_customers?.toLocaleString() || 'N/A'}</td>
                    `;
          break;

        case 'monthly':
          row = `
                        <td class="py-3 px-4">${item.month_name}</td>
                        <td class="py-3 px-4">${item.order_count.toLocaleString()}</td>
                        <td class="py-3 px-4 font-bold">${item.total_revenue_formatted}</td>
                        <td class="py-3 px-4">${item.avg_order_value_formatted}</td>
                        <td class="py-3 px-4">${item.unique_customers?.toLocaleString() || 'N/A'}</td>
                    `;
          break;

        case 'yearly':
          row = `
                        <td class="py-3 px-4">${item.year}</td>
                        <td class="py-3 px-4">${item.order_count.toLocaleString()}</td>
                        <td class="py-3 px-4 font-bold">${item.total_revenue_formatted}</td>
                        <td class="py-3 px-4">${item.avg_order_value_formatted}</td>
                        <td class="py-3 px-4">${item.unique_customers?.toLocaleString() || 'N/A'}</td>
                    `;
          break;

        case 'category':
          row = `
                        <td class="py-3 px-4">
                            <div class="flex items-center gap-2">
                                <div class="h-2 w-2 rounded-full bg-primary"></div>
                                ${item.category_name}
                            </div>
                        </td>
                        <td class="py-3 px-4">${item.order_count.toLocaleString()}</td>
                        <td class="py-3 px-4 font-bold">${item.total_revenue_formatted}</td>
                        <td class="py-3 px-4">${item.total_quantity.toLocaleString()}</td>
                        <td class="py-3 px-4">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                    <div class="h-full bg-primary" style="width: ${item.percentage}%"></div>
                                </div>
                                <span class="text-sm font-medium">${item.percentage}%</span>
                            </div>
                        </td>
                    `;
          break;

        case 'food':
          row = `
                        <td class="py-3 px-4">
                            <div class="font-medium">${item.food_name}</div>
                            <div class="text-xs text-[#9c7349]">${item.price_formatted}</div>
                        </td>
                        <td class="py-3 px-4">${item.category_name}</td>
                        <td class="py-3 px-4">${item.total_quantity.toLocaleString()}</td>
                        <td class="py-3 px-4 font-bold">${item.total_revenue_formatted}</td>
                        <td class="py-3 px-4">
                            <div class="flex items-center gap-1">
                                <span class="material-symbols-outlined text-yellow-500 text-sm fill">star</span>
                                <span>${item.avg_rating || 0}</span>
                            </div>
                        </td>
                        <td class="py-3 px-4">
                            <span class="text-sm font-medium">${item.percentage}%</span>
                        </td>
                    `;
          break;

        case 'payment':
          row = `
                        <td class="py-3 px-4">
                            <div class="flex items-center gap-2">
                                <div class="h-8 w-8 bg-primary/10 text-primary rounded-lg flex items-center justify-center">
                                    <span class="material-symbols-outlined text-sm">credit_card</span>
                                </div>
                                ${item.payment_method_name}
                            </div>
                        </td>
                        <td class="py-3 px-4">${item.order_count.toLocaleString()}</td>
                        <td class="py-3 px-4 font-bold">${item.total_revenue_formatted}</td>
                        <td class="py-3 px-4">${item.avg_order_value_formatted}</td>
                        <td class="py-3 px-4">
                            <span class="text-sm font-medium">${item.percentage}%</span>
                        </td>
                    `;
          break;

        case 'time':
          const ordersPerHour = item.order_count;
          const revenuePerHour = item.total_revenue;
          row = `
                        <td class="py-3 px-4 font-medium">${item.hour_formatted}</td>
                        <td class="py-3 px-4">${ordersPerHour.toLocaleString()}</td>
                        <td class="py-3 px-4 font-bold">${item.total_revenue_formatted}</td>
                        <td class="py-3 px-4">${ordersPerHour}</td>
                        <td class="py-3 px-4">${revenuePerHour.toLocaleString('vi-VN')}đ</td>
                    `;
          break;
      }

      tableRows += `
                <tr class="table-row-hover border-b border-[#f4ede7] dark:border-[#3d2e1f]">
                    ${row}
                </tr>
            `;
    });

    tableBody.innerHTML = tableRows;

    // Update table title
    document.getElementById('table-title').textContent =
      `Chi tiết doanh thu ${this.getReportTypeName()}`;
  }

  updateTablePagination() {
    const totalPages = Math.ceil(this.totalItems / this.itemsPerPage);

    document.getElementById('current-page').value = this.currentPage;
    document.getElementById('total-pages').textContent = totalPages;

    document.getElementById('prev-page').disabled = this.currentPage <= 1;
    document.getElementById('next-page').disabled =
      this.currentPage >= totalPages;

    const startItem = (this.currentPage - 1) * this.itemsPerPage + 1;
    const endItem = Math.min(
      this.currentPage * this.itemsPerPage,
      this.totalItems,
    );

    document.getElementById('table-info').textContent =
      `Hiển thị ${startItem} đến ${endItem} của ${this.totalItems} bản ghi`;
  }

  prevPage() {
    if (this.currentPage > 1) {
      this.currentPage--;
      this.renderTable(this.currentData);
    }
  }

  nextPage() {
    const totalPages = Math.ceil(this.totalItems / this.itemsPerPage);
    if (this.currentPage < totalPages) {
      this.currentPage++;
      this.renderTable(this.currentData);
    }
  }

  goToPage(page) {
    const totalPages = Math.ceil(this.totalItems / this.itemsPerPage);
    if (page >= 1 && page <= totalPages) {
      this.currentPage = page;
      this.renderTable(this.currentData);
    }
  }

  filterTable(searchTerm) {
    if (!this.currentData || !this.currentData.data) return;

    const filteredData = this.currentData.data.filter((item) => {
      const searchLower = searchTerm.toLowerCase();

      switch (this.currentReportType) {
        case 'daily':
          return item.date_formatted.toLowerCase().includes(searchLower);
        case 'monthly':
          return item.month_name.toLowerCase().includes(searchLower);
        case 'yearly':
          return item.year.toString().includes(searchLower);
        case 'category':
          return item.category_name.toLowerCase().includes(searchLower);
        case 'food':
          return (
            item.food_name.toLowerCase().includes(searchLower) ||
            item.category_name.toLowerCase().includes(searchLower)
          );
        case 'payment':
          return item.payment_method_name.toLowerCase().includes(searchLower);
        case 'time':
          return item.hour_formatted.toLowerCase().includes(searchLower);
        default:
          return true;
      }
    });

    this.totalItems = filteredData.length;
    this.currentPage = 1;

    // Create a modified data object with filtered data
    const filteredDataObj = {
      ...this.currentData,
      data: filteredData,
    };

    this.renderTable(filteredDataObj);
  }

  updateAnalysis(data) {
    if (!data || !data.data) return;

    // Revenue trend analysis
    if (data.data.length >= 2 && this.currentReportType === 'daily') {
      const lastItem = data.data[data.data.length - 1];
      const secondLastItem = data.data[data.data.length - 2];

      const lastRevenue = lastItem.total_revenue;
      const prevRevenue = secondLastItem.total_revenue;

      let trend = '';
      let trendDetail = '';

      if (lastRevenue > prevRevenue) {
        const growth = ((lastRevenue - prevRevenue) / prevRevenue) * 100;
        trend = `Tăng ${growth.toFixed(1)}% so với hôm qua`;
        trendDetail = `Doanh thu hôm nay (${lastItem.date_formatted}) đạt ${lastItem.total_revenue_formatted}, tăng ${growth.toFixed(1)}% so với hôm qua (${secondLastItem.total_revenue_formatted}).`;
      } else if (lastRevenue < prevRevenue) {
        const decline = ((prevRevenue - lastRevenue) / prevRevenue) * 100;
        trend = `Giảm ${decline.toFixed(1)}% so với hôm qua`;
        trendDetail = `Doanh thu hôm nay (${lastItem.date_formatted}) đạt ${lastItem.total_revenue_formatted}, giảm ${decline.toFixed(1)}% so với hôm qua (${secondLastItem.total_revenue_formatted}).`;
      } else {
        trend = 'Ổn định so với hôm qua';
        trendDetail = `Doanh thu hôm nay (${lastItem.date_formatted}) đạt ${lastItem.total_revenue_formatted}, bằng với hôm qua.`;
      }

      document.getElementById('revenue-trend').textContent = trend;
      document.getElementById('revenue-trend-detail').textContent = trendDetail;
    }

    // Peak hours analysis
    if (this.currentReportType === 'time') {
      const peakHours = data.data
        .filter((item) => item.total_revenue > 0)
        .sort((a, b) => b.total_revenue - a.total_revenue)
        .slice(0, 3);

      if (peakHours.length > 0) {
        const peakHoursText = peakHours.map((h) => h.hour_formatted).join(', ');
        const peakRevenue = peakHours[0].total_revenue_formatted;

        document.getElementById('peak-hours').textContent = peakHoursText;
        document.getElementById('peak-hours-detail').textContent =
          `Giờ cao điểm nhất là ${peakHours[0].hour_formatted} với doanh thu ${peakRevenue}.`;

        // Peak hour recommendation
        document.getElementById('peak-hour-recommendation').textContent =
          `Tăng cường nhân sự và chuẩn bị nguyên liệu vào các khung giờ ${peakHoursText} để đáp ứng nhu cầu cao nhất.`;
      }
    }

    // Top products analysis
    if (this.currentReportType === 'food' && data.data.length > 0) {
      const topProducts = data.data.slice(0, 3);
      const topProductsText = topProducts.map((p) => p.food_name).join(', ');

      document.getElementById('top-products').textContent = topProductsText;
      document.getElementById('top-products-detail').textContent =
        `${topProducts[0].food_name} đứng đầu với doanh thu ${topProducts[0].total_revenue_formatted} (${topProducts[0].percentage}%).`;

      // Product recommendation
      document.getElementById('product-recommendation').textContent =
        `Tập trung quảng bá ${topProducts[0].food_name} và phát triển combo từ các món bán chạy: ${topProductsText}.`;
    }

    // Promotion recommendation
    const totalRevenue = data.summary?.total_revenue || 0;
    if (totalRevenue > 0) {
      document.getElementById('promotion-recommendation').textContent =
        `Tạo chương trình khuyến mãi cho các món ít bán chạy hoặc combo kết hợp với đồ uống để tăng giá trị đơn hàng trung bình.`;
    }

    // Update metrics
    this.updateAnalysisMetrics(data);
  }

  updateAnalysisMetrics(data) {
    // These are sample calculations - in a real app, you would calculate these from actual data

    // Conversion rate (sample)
    const conversionRate = 15.3;
    document.getElementById('metric-conversion-rate').textContent =
      `${conversionRate}%`;

    // Customer lifetime value (sample)
    const customerValue = 850000;
    document.getElementById('metric-customer-value').textContent =
      customerValue.toLocaleString('vi-VN') + 'đ';

    // Repeat customer rate (sample)
    const repeatRate = 42.7;
    document.getElementById('metric-repeat-rate').textContent =
      `${repeatRate}%`;

    // Profit margin (sample)
    const margin = 35.2;
    document.getElementById('metric-margin').textContent = `${margin}%`;
  }

  updateComparisonStats(data) {
    const current = data.current_period;
    const comparison = data.comparison_period;
    const growth = data.growth;

    // Update comparison stats
    document.getElementById('current-revenue-comp').textContent =
      current.total_revenue_formatted;
    document.getElementById('comparison-revenue').textContent =
      comparison.total_revenue_formatted;

    const difference = current.total_revenue - comparison.total_revenue;
    document.getElementById('revenue-difference').textContent =
      difference.toLocaleString('vi-VN') + 'đ';

    const growthElement = document.getElementById('revenue-growth-percent');
    growthElement.textContent = `${growth >= 0 ? '+' : ''}${growth}%`;

    if (growth > 0) {
      growthElement.className = 'font-bold trend-up';
    } else if (growth < 0) {
      growthElement.className = 'font-bold trend-down';
    } else {
      growthElement.className = 'font-bold trend-neutral';
    }

    // Update other metrics
    document.getElementById('current-orders').textContent =
      current.order_count.toLocaleString();
    document.getElementById('current-avg-order').textContent =
      current.avg_order_value.toLocaleString('vi-VN') + 'đ';
    document.getElementById('current-new-customers').textContent =
      current.unique_customers.toLocaleString();
    document.getElementById('current-conversion').textContent = '15.3%';

    // Calculate growth percentages for other metrics
    const orderGrowth =
      comparison.order_count > 0
        ? ((current.order_count - comparison.order_count) /
            comparison.order_count) *
          100
        : 0;
    document.getElementById('order-growth').textContent =
      `${orderGrowth >= 0 ? '+' : ''}${orderGrowth.toFixed(1)}%`;

    const avgOrderGrowth =
      comparison.avg_order_value > 0
        ? ((current.avg_order_value - comparison.avg_order_value) /
            comparison.avg_order_value) *
          100
        : 0;
    document.getElementById('avg-order-growth').textContent =
      `${avgOrderGrowth >= 0 ? '+' : ''}${avgOrderGrowth.toFixed(1)}%`;

    const customerGrowth =
      comparison.unique_customers > 0
        ? ((current.unique_customers - comparison.unique_customers) /
            comparison.unique_customers) *
          100
        : 0;
    document.getElementById('customer-growth').textContent =
      `${customerGrowth >= 0 ? '+' : ''}${customerGrowth.toFixed(1)}%`;
  }

  switchTab(tabId) {
    // Update active tab button
    document.querySelectorAll('.tab-button').forEach((tab) => {
      tab.classList.remove('active');
      if (tab.dataset.tab === tabId) {
        tab.classList.add('active');
      }
    });

    // Show active tab content
    document.querySelectorAll('.tab-content').forEach((content) => {
      content.classList.add('hidden');
      content.classList.remove('active');
    });

    const activeTab = document.getElementById(`${tabId}-tab`);
    if (activeTab) {
      activeTab.classList.remove('hidden');
      activeTab.classList.add('active');
    }

    // Load data for specific tabs
    if (tabId === 'comparison') {
      this.loadComparisonData();
    }
  }

  updateChartType(chartType) {
    if (this.revenueChart) {
      this.revenueChart.config.type = chartType;

      // Update fill option for area charts
      if (chartType === 'area') {
        this.revenueChart.config.data.datasets[0].fill = true;
      } else {
        this.revenueChart.config.data.datasets[0].fill = false;
      }

      this.revenueChart.update();
    }
  }

  downloadChart() {
    if (this.revenueChart) {
      const link = document.createElement('a');
      link.download = `doanh-thu-${new Date().toISOString().split('T')[0]}.png`;
      link.href = this.revenueChart.toBase64Image();
      link.click();

      this.showToast('Thành công', 'Đã tải xuống biểu đồ', 'success');
    }
  }

  openExportModal() {
    const modal = document.getElementById('export-modal');
    if (modal) {
      modal.classList.remove('hidden');
      modal.classList.add('flex');

      // Set default values
      document.getElementById('export-start-date').value =
        this.currentFilters.startDate;
      document.getElementById('export-end-date').value =
        this.currentFilters.endDate;
      document.getElementById('export-report-type').value =
        this.currentReportType;
    }
  }

  closeExportModal() {
    const modal = document.getElementById('export-modal');
    if (modal) {
      modal.classList.add('hidden');
      modal.classList.remove('flex');
    }
  }

  async processExport() {
    const format = document.getElementById('export-format')?.value || 'pdf';
    const reportType =
      document.getElementById('export-report-type')?.value || 'daily';
    const startDate =
      document.getElementById('export-start-date')?.value ||
      this.currentFilters.startDate;
    const endDate =
      document.getElementById('export-end-date')?.value ||
      this.currentFilters.endDate;
    const filename =
      document.getElementById('export-filename')?.value || 'bao-cao-doanh-thu';

    try {
      const data = await this.fetchAPI('laydulieu_doanhthu.php', {
        action: 'export_revenue_report',
        type: reportType,
        start_date: startDate,
        end_date: endDate,
        format: format,
      });

      if (data.success) {
        // For JSON format, create download
        if (format === 'json') {
          const blob = new Blob([JSON.stringify(data, null, 2)], {
            type: 'application/json',
          });
          const url = URL.createObjectURL(blob);
          const a = document.createElement('a');
          a.href = url;
          a.download = `${filename}.json`;
          a.click();
          URL.revokeObjectURL(url);
        }

        this.showToast(
          'Thành công',
          `Đã xuất báo cáo ${format.toUpperCase()}`,
          'success',
        );
        this.closeExportModal();
      }
    } catch (error) {
      console.error('Error exporting report:', error);
      this.showToast('Lỗi', 'Không thể xuất báo cáo', 'error');
    }
  }

  exportTable() {
    if (!this.currentData || !this.currentData.data) return;

    // Create CSV content
    let csvContent = '';

    // Add headers
    const headers = this.getTableHeaders();
    csvContent += headers.join(',') + '\n';

    // Add data rows
    this.currentData.data.forEach((item) => {
      const row = this.getTableRowAsCSV(item);
      csvContent += row.join(',') + '\n';
    });

    // Create download
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `doanh-thu-${new Date().toISOString().split('T')[0]}.csv`;
    a.click();
    URL.revokeObjectURL(url);

    this.showToast('Thành công', 'Đã xuất bảng dữ liệu ra file CSV', 'success');
  }

  getTableHeaders() {
    switch (this.currentReportType) {
      case 'daily':
        return ['Ngày', 'Số đơn', 'Doanh thu', 'Đơn trung bình', 'Khách hàng'];
      case 'monthly':
        return ['Tháng', 'Số đơn', 'Doanh thu', 'Đơn trung bình', 'Khách hàng'];
      case 'yearly':
        return ['Năm', 'Số đơn', 'Doanh thu', 'Đơn trung bình', 'Khách hàng'];
      case 'category':
        return ['Danh mục', 'Số đơn', 'Doanh thu', 'Số lượng', 'Tỷ lệ %'];
      case 'food':
        return [
          'Món ăn',
          'Danh mục',
          'Đã bán',
          'Doanh thu',
          'Đánh giá',
          'Tỷ lệ %',
        ];
      case 'payment':
        return [
          'Phương thức',
          'Số đơn',
          'Doanh thu',
          'Đơn trung bình',
          'Tỷ lệ %',
        ];
      case 'time':
        return ['Khung giờ', 'Số đơn', 'Doanh thu', 'Đơn/giờ', 'Doanh thu/giờ'];
      default:
        return [];
    }
  }

  getTableRowAsCSV(item) {
    switch (this.currentReportType) {
      case 'daily':
        return [
          item.date_formatted,
          item.order_count,
          item.total_revenue,
          item.avg_order_value,
          item.unique_customers || '',
        ];
      case 'monthly':
        return [
          item.month_name,
          item.order_count,
          item.total_revenue,
          item.avg_order_value,
          item.unique_customers || '',
        ];
      case 'yearly':
        return [
          item.year,
          item.order_count,
          item.total_revenue,
          item.avg_order_value,
          item.unique_customers || '',
        ];
      case 'category':
        return [
          item.category_name,
          item.order_count,
          item.total_revenue,
          item.total_quantity,
          item.percentage,
        ];
      case 'food':
        return [
          item.food_name,
          item.category_name,
          item.total_quantity,
          item.total_revenue,
          item.avg_rating || 0,
          item.percentage,
        ];
      case 'payment':
        return [
          item.payment_method_name,
          item.order_count,
          item.total_revenue,
          item.avg_order_value,
          item.percentage,
        ];
      case 'time':
        return [
          item.hour_formatted,
          item.order_count,
          item.total_revenue,
          item.order_count,
          item.total_revenue,
        ];
      default:
        return [];
    }
  }

  getReportTypeName() {
    switch (this.currentReportType) {
      case 'daily':
        return 'theo ngày';
      case 'monthly':
        return 'theo tháng';
      case 'yearly':
        return 'theo năm';
      case 'category':
        return 'theo danh mục';
      case 'food':
        return 'theo món ăn';
      case 'payment':
        return 'theo phương thức thanh toán';
      case 'time':
        return 'theo khung giờ';
      default:
        return '';
    }
  }

  refreshData() {
    this.loadRevenueSummary();
    this.loadRevenueData();
    this.showToast('Đang làm mới...', 'Dữ liệu đang được cập nhật', 'info');
  }

  // Utility methods
  async fetchAPI(endpoint, params = {}) {
    try {
      const queryString = new URLSearchParams(params).toString();
      const url = `${this.API_BASE_URL}/${endpoint}${queryString ? '?' + queryString : ''}`;

      const response = await fetch(url);
      if (!response.ok)
        throw new Error(`HTTP error! status: ${response.status}`);

      return await response.json();
    } catch (error) {
      console.error('API Error:', error);
      return { success: false, message: error.message };
    }
  }

  showLoading() {
    const spinner = document.getElementById('loading-spinner');
    if (spinner) {
      spinner.classList.remove('hidden');
    }
  }

  hideLoading() {
    const spinner = document.getElementById('loading-spinner');
    if (spinner) {
      spinner.classList.add('hidden');
    }
  }

  showToast(title, message, type = 'success') {
    const toast = document.getElementById('toast');
    const toastIcon = document.getElementById('toast-icon');

    if (!toast || !toastIcon) return;

    if (type === 'error') {
      toastIcon.className =
        'h-10 w-10 bg-red-100 text-red-600 rounded-full flex items-center justify-center';
      toastIcon.innerHTML =
        '<span class="material-symbols-outlined">error</span>';
    } else if (type === 'warning') {
      toastIcon.className =
        'h-10 w-10 bg-yellow-100 text-yellow-600 rounded-full flex items-center justify-center';
      toastIcon.innerHTML =
        '<span class="material-symbols-outlined">warning</span>';
    } else if (type === 'info') {
      toastIcon.className =
        'h-10 w-10 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center';
      toastIcon.innerHTML =
        '<span class="material-symbols-outlined">info</span>';
    } else {
      toastIcon.className =
        'h-10 w-10 bg-green-100 text-green-600 rounded-full flex items-center justify-center';
      toastIcon.innerHTML =
        '<span class="material-symbols-outlined">check_circle</span>';
    }

    document.getElementById('toast-title').textContent = title;
    document.getElementById('toast-message').textContent = message;

    toast.classList.remove('hidden');
    toast.style.animation = 'fadeIn 0.3s ease-in';

    setTimeout(() => this.hideToast(), 3000);
  }

  hideToast() {
    const toast = document.getElementById('toast');
    if (toast) {
      toast.classList.add('hidden');
    }
  }
}

// Initialize the revenue report when page loads
document.addEventListener('DOMContentLoaded', function () {
  window.revenueReport = new RevenueReport();
});

// Export for use in other files
if (typeof module !== 'undefined' && module.exports) {
  module.exports = RevenueReport;
}

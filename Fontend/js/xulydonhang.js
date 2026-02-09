// Xử lý báo cáo đơn hàng
let donHangChart = null;
let trangThaiChart = null;
let currentData = [];
let currentPage = 1;
const itemsPerPage = 10;

// Khởi tạo biểu đồ
function initCharts() {
  const donHangCtx = document.getElementById('donHangChart').getContext('2d');
  const trangThaiCtx = document
    .getElementById('trangThaiChart')
    .getContext('2d');

  // Biểu đồ đơn hàng
  donHangChart = new Chart(donHangCtx, {
    type: 'bar',
    data: {
      labels: [],
      datasets: [
        {
          label: 'Tổng đơn hàng',
          data: [],
          backgroundColor: '#e8956f',
          borderColor: '#d87c54',
          borderWidth: 1,
        },
        {
          label: 'Đơn thành công',
          data: [],
          backgroundColor: '#10b981',
          borderColor: '#0da271',
          borderWidth: 1,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          labels: {
            color: window.matchMedia('(prefers-color-scheme: dark)').matches
              ? '#fff'
              : '#1c140d',
          },
        },
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            color: window.matchMedia('(prefers-color-scheme: dark)').matches
              ? '#fff'
              : '#1c140d',
          },
          grid: {
            color: window.matchMedia('(prefers-color-scheme: dark)').matches
              ? '#3d2e1f'
              : '#f4ede7',
          },
        },
        x: {
          ticks: {
            color: window.matchMedia('(prefers-color-scheme: dark)').matches
              ? '#fff'
              : '#1c140d',
          },
          grid: {
            color: window.matchMedia('(prefers-color-scheme: dark)').matches
              ? '#3d2e1f'
              : '#f4ede7',
          },
        },
      },
    },
  });

  // Biểu đồ trạng thái
  trangThaiChart = new Chart(trangThaiCtx, {
    type: 'doughnut',
    data: {
      labels: [],
      datasets: [
        {
          data: [],
          backgroundColor: [
            '#10b981', // da_giao
            '#3b82f6', // dang_giao
            '#f59e0b', // cho_xac_nhan
            '#ef4444', // da_huy
            '#8b5cf6', // da_xac_nhan
          ],
          borderWidth: 1,
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
              : '#1c140d',
          },
        },
      },
    },
  });
}

// Tải dữ liệu đơn hàng
async function loadDonHangData() {
  showLoading();

  const loaiThongKe = document.getElementById('loaiThongKe').value;
  const nam = document.getElementById('selectNam').value;
  const thang = document.getElementById('selectThang').value;
  const namBatDau = document.getElementById('namBatDau')?.value;
  const namKetThuc = document.getElementById('namKetThuc')?.value;

  let url = `../../api/laydulieu_donhang.php?loai=${loaiThongKe}`;

  if (
    loaiThongKe === 'theo_thang' ||
    loaiThongKe === 'chi_tiet_thang' ||
    loaiThongKe === 'trang_thai'
  ) {
    url += `&nam=${nam}&thang=${thang}`;
  } else if (loaiThongKe === 'theo_nam') {
    url += `&nam_bat_dau=${namBatDau}&nam_ket_thuc=${namKetThuc}`;
  }

  try {
    const response = await fetch(url);
    const result = await response.json();

    if (result.success) {
      currentData = result.data;

      // Cập nhật summary cards
      updateSummaryCards(result.summary);

      // Cập nhật biểu đồ
      updateCharts(result.data, loaiThongKe);

      // Cập nhật bảng
      updateTable();

      // Cập nhật insights
      generateInsights(result.data, result.summary);

      // Tải thêm danh sách đơn hàng gần đây nếu không phải là loại đó
      if (loaiThongKe !== 'donhang_gan_day') {
        loadDonHangGanDay();
      }
    } else {
      showError(result.message || 'Không thể tải dữ liệu');
    }
  } catch (error) {
    showError('Lỗi kết nối: ' + error.message);
    console.error('Error loading data:', error);
  } finally {
    hideLoading();
  }
}

// Tải đơn hàng gần đây
async function loadDonHangGanDay() {
  try {
    const response = await fetch(
      '../../api/laydulieu_donhang.php?loai=donhang_gan_day&limit=5',
    );
    const result = await response.json();

    if (result.success) {
      updateRecentOrdersTable(result.data);
    }
  } catch (error) {
    console.error('Error loading recent orders:', error);
  }
}

// Cập nhật summary cards
function updateSummaryCards(summary) {
  const container = document.getElementById('summaryCards');

  if (!summary) {
    container.innerHTML = `
            <div class="col-span-4 text-center py-8 text-gray-500">
                <i class="fas fa-chart-bar text-3xl mb-2"></i>
                <p>Chọn loại thống kê để xem tổng quan</p>
            </div>
        `;
    return;
  }

  const cards = [
    {
      title: 'Tổng đơn hàng',
      value: summary.tong_donhang?.toLocaleString() || '0',
      icon: 'fas fa-shopping-cart',
      color: 'bg-blue-500',
      change: summary.ty_le_thanhcong
        ? `${summary.ty_le_thanhcong}% thành công`
        : '',
    },
    {
      title: 'Tổng doanh thu',
      value: (summary.tong_doanhthu?.toFixed(0) || '0') + ' đ',
      icon: 'fas fa-money-bill-wave',
      color: 'bg-green-500',
      change: summary.avg_gia_tri_donhang
        ? `Trung bình: ${summary.avg_gia_tri_donhang.toFixed(0)}đ/đơn`
        : '',
    },
    {
      title: 'Đơn thành công',
      value: summary.tong_donhang_thanhcong?.toLocaleString() || '0',
      icon: 'fas fa-check-circle',
      color: 'bg-purple-500',
      change: summary.ty_le_thanhcong
        ? `${summary.ty_le_thanhcong}% tỷ lệ`
        : '',
    },
    {
      title: 'Đơn đã hủy',
      value: summary.tong_donhang_huy?.toLocaleString() || '0',
      icon: 'fas fa-times-circle',
      color: 'bg-red-500',
      change: summary.tong_donhang_huy
        ? `${((summary.tong_donhang_huy / summary.tong_donhang) * 100).toFixed(1)}% tổng đơn`
        : '',
    },
  ];

  container.innerHTML = cards
    .map(
      (card) => `
        <div class="card">
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">${card.title}</p>
                        <p class="text-2xl font-bold text-[#1c140d] dark:text-white mt-2">${card.value}</p>
                        ${card.change ? `<p class="text-xs text-gray-500 dark:text-gray-400 mt-1">${card.change}</p>` : ''}
                    </div>
                    <div class="${card.color} w-12 h-12 rounded-full flex items-center justify-center">
                        <i class="${card.icon} text-white text-xl"></i>
                    </div>
                </div>
            </div>
        </div>
    `,
    )
    .join('');
}

// Cập nhật biểu đồ
function updateCharts(data, loaiThongKe) {
  if (!donHangChart || !trangThaiChart) return;

  switch (loaiThongKe) {
    case 'theo_thang':
      // Biểu đồ theo tháng
      const months = [
        'T1',
        'T2',
        'T3',
        'T4',
        'T5',
        'T6',
        'T7',
        'T8',
        'T9',
        'T10',
        'T11',
        'T12',
      ];
      const monthData = Array(12).fill(0);
      const monthSuccessData = Array(12).fill(0);

      data.forEach((item) => {
        const idx = item.thang - 1;
        monthData[idx] = item.tong_donhang;
        monthSuccessData[idx] = item.donhang_thanhcong;
      });

      donHangChart.data.labels = months;
      donHangChart.data.datasets[0].data = monthData;
      donHangChart.data.datasets[1].data = monthSuccessData;
      donHangChart.options.scales.x.title = { display: true, text: 'Tháng' };
      break;

    case 'theo_nam':
      // Biểu đồ theo năm
      const years = data.map((item) => item.nam);
      const yearData = data.map((item) => item.tong_donhang);
      const yearSuccessData = data.map((item) => item.donhang_thanhcong);

      donHangChart.data.labels = years;
      donHangChart.data.datasets[0].data = yearData;
      donHangChart.data.datasets[1].data = yearSuccessData;
      donHangChart.options.scales.x.title = { display: true, text: 'Năm' };
      break;

    case 'chi_tiet_thang':
      // Biểu đồ chi tiết theo ngày
      const days = data.map((item) => {
        const date = new Date(item.ngay);
        return `${date.getDate()}/${date.getMonth() + 1}`;
      });
      const dayData = data.map((item) => item.tong_donhang);
      const daySuccessData = data.map((item) => item.donhang_thanhcong);

      donHangChart.data.labels = days;
      donHangChart.data.datasets[0].data = dayData;
      donHangChart.data.datasets[1].data = daySuccessData;
      donHangChart.options.scales.x.title = { display: true, text: 'Ngày' };
      break;

    case 'trang_thai':
      // Biểu đồ trạng thái
      const statusLabels = {
        cho_xac_nhan: 'Chờ xác nhận',
        da_xac_nhan: 'Đã xác nhận',
        dang_giao: 'Đang giao',
        da_giao: 'Đã giao',
        da_huy: 'Đã hủy',
      };

      const statusData = {};
      data.forEach((item) => {
        const label = statusLabels[item.trang_thai] || item.trang_thai;
        statusData[label] = (statusData[label] || 0) + item.so_luong;
      });

      trangThaiChart.data.labels = Object.keys(statusData);
      trangThaiChart.data.datasets[0].data = Object.values(statusData);
      break;

    case 'phuong_thuc_thanh_toan':
      // Biểu đồ phương thức thanh toán
      const paymentLabels = {
        tien_mat: 'Tiền mặt',
        the_ngan_hang: 'Thẻ ngân hàng',
        vi_dien_tu: 'Ví điện tử',
      };

      const paymentData = {};
      data.forEach((item) => {
        const label = paymentLabels[item.phuong_thuc] || item.phuong_thuc;
        paymentData[label] = (paymentData[label] || 0) + item.so_luong;
      });

      trangThaiChart.data.labels = Object.keys(paymentData);
      trangThaiChart.data.datasets[0].data = Object.values(paymentData);
      break;
  }

  donHangChart.update();
  trangThaiChart.update();
}

// Cập nhật bảng
function updateTable() {
  const tableBody = document.getElementById('tableBody');
  const currentCount = document.getElementById('currentCount');
  const totalCount = document.getElementById('totalCount');

  if (!currentData || currentData.length === 0) {
    tableBody.innerHTML = `
            <tr>
                <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                    <i class="fas fa-inbox text-3xl mb-2"></i>
                    <p>Không có dữ liệu đơn hàng</p>
                </td>
            </tr>
        `;
    currentCount.textContent = '0';
    totalCount.textContent = '0';
    document.getElementById('pagination').innerHTML = '';
    return;
  }

  // Tính toán phân trang
  const totalItems = currentData.length;
  const totalPages = Math.ceil(totalItems / itemsPerPage);
  const startIndex = (currentPage - 1) * itemsPerPage;
  const endIndex = Math.min(startIndex + itemsPerPage, totalItems);
  const pageData = currentData.slice(startIndex, endIndex);

  // Cập nhật bảng
  tableBody.innerHTML = pageData
    .map((item) => {
      // Format dựa trên loại thống kê
      let rowHtml = '';

      if (item.donhang_id) {
        // Định dạng cho danh sách đơn hàng
        rowHtml = `
                <tr class="hover:bg-gray-50 dark:hover:bg-[#3d2e1f] transition-colors">
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="font-medium text-[#1c140d] dark:text-white">${item.donhang_id}</span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                        ${formatDate(item.ngay_tao)}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <div>
                            <div class="font-medium text-[#1c140d] dark:text-white">${item.hoten}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">${item.sodienthoai}</div>
                        </div>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-center">
                        <span class="px-2 py-1 text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded-full">
                            ${item.so_mon}
                        </span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap font-medium text-[#1c140d] dark:text-white">
                        ${formatCurrency(item.tong_cuoi_cung)}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-medium rounded-full ${getPaymentMethodClass(item.phuong_thuc_thanhtoan)}">
                            ${formatPaymentMethod(item.phuong_thuc_thanhtoan)}
                        </span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        ${formatOrderStatus(item.trang_thai_donhang)}
                    </td>
                </tr>
            `;
      } else if (item.trang_thai) {
        // Định dạng cho thống kê trạng thái
        rowHtml = `
                <tr class="hover:bg-gray-50 dark:hover:bg-[#3d2e1f] transition-colors">
                    <td class="px-4 py-3 whitespace-nowrap text-center" colspan="2">
                        <span class="font-medium text-[#1c140d] dark:text-white">${formatOrderStatus(item.trang_thai)}</span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-center">
                        <span class="font-medium text-[#1c140d] dark:text-white">${item.so_luong}</span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">${item.phan_tram}%</span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap font-medium text-[#1c140d] dark:text-white">
                        ${formatCurrency(item.tong_doanhthu)}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-center">
                        ${formatCurrency(item.avg_gia_tri)}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-[#e8956f] h-2 rounded-full" style="width: ${item.phan_tram}%"></div>
                        </div>
                    </td>
                </tr>
            `;
      } else {
        // Định dạng mặc định
        rowHtml = `
                <tr class="hover:bg-gray-50 dark:hover:bg-[#3d2e1f] transition-colors">
                    <td class="px-4 py-3 whitespace-nowrap text-center" colspan="7">
                        <span class="text-gray-600 dark:text-gray-400">Không thể hiển thị dữ liệu này dưới dạng bảng</span>
                    </td>
                </tr>
            `;
      }

      return rowHtml;
    })
    .join('');

  // Cập nhật phân trang
  updatePagination(totalPages);

  // Cập nhật số lượng
  currentCount.textContent = pageData.length;
  totalCount.textContent = totalItems;
}

// Cập nhật phân trang
function updatePagination(totalPages) {
  const pagination = document.getElementById('pagination');

  if (totalPages <= 1) {
    pagination.innerHTML = '';
    return;
  }

  let paginationHtml = `
        <button onclick="changePage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''} 
                class="px-3 py-1 rounded-lg ${currentPage === 1 ? 'bg-gray-100 dark:bg-gray-700 text-gray-400' : 'bg-[#e8956f] text-white hover:opacity-90'}">
            <i class="fas fa-chevron-left"></i>
        </button>
    `;

  // Hiển thị tối đa 5 trang
  const startPage = Math.max(1, currentPage - 2);
  const endPage = Math.min(totalPages, startPage + 4);

  for (let i = startPage; i <= endPage; i++) {
    paginationHtml += `
            <button onclick="changePage(${i})" 
                    class="px-3 py-1 rounded-lg ${currentPage === i ? 'bg-[#e8956f] text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'}">
                ${i}
            </button>
        `;
  }

  paginationHtml += `
        <button onclick="changePage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''} 
                class="px-3 py-1 rounded-lg ${currentPage === totalPages ? 'bg-gray-100 dark:bg-gray-700 text-gray-400' : 'bg-[#e8956f] text-white hover:opacity-90'}">
            <i class="fas fa-chevron-right"></i>
        </button>
    `;

  pagination.innerHTML = paginationHtml;
}

// Thay đổi trang
function changePage(page) {
  if (page < 1 || page > Math.ceil(currentData.length / itemsPerPage)) return;
  currentPage = page;
  updateTable();
}

// Cập nhật bảng đơn hàng gần đây
function updateRecentOrdersTable(data) {
  const container = document.createElement('div');

  if (!data || data.length === 0) return;

  // Tạo một bảng nhỏ cho đơn hàng gần đây
  const html = `
        <div class="mt-4">
            <h3 class="text-lg font-medium text-[#1c140d] dark:text-white mb-2">Đơn hàng gần đây</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-[#3d2e1f]">
                            <th class="py-2 text-left">Mã đơn</th>
                            <th class="py-2 text-left">Khách hàng</th>
                            <th class="py-2 text-left">Tổng tiền</th>
                            <th class="py-2 text-left">Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${data
                          .map(
                            (order) => `
                            <tr class="border-b border-gray-200 dark:border-[#3d2e1f] last:border-0">
                                <td class="py-2">${order.donhang_id}</td>
                                <td class="py-2">${order.hoten}</td>
                                <td class="py-2">${formatCurrency(order.tong_cuoi_cung)}</td>
                                <td class="py-2">${formatOrderStatus(order.trang_thai_donhang)}</td>
                            </tr>
                        `,
                          )
                          .join('')}
                    </tbody>
                </table>
            </div>
        </div>
    `;

  // Thêm vào insights container hoặc tạo mới
  const insightsContainer = document.getElementById('insightsContainer');
  if (insightsContainer) {
    insightsContainer.insertAdjacentHTML('beforeend', html);
  }
}

// Tạo insights và đề xuất
function generateInsights(data, summary) {
  const container = document.getElementById('insightsContainer');
  if (!container) return;

  let insights = [];

  if (summary) {
    // Insight 1: Tỷ lệ thành công
    if (summary.ty_le_thanhcong < 70) {
      insights.push({
        icon: 'fas fa-exclamation-triangle',
        color: 'text-yellow-500',
        title: 'Tỷ lệ thành công thấp',
        content: `Chỉ ${summary.ty_le_thanhcong}% đơn hàng thành công. Cần xem xét lại quy trình xử lý đơn hàng.`,
      });
    } else if (summary.ty_le_thanhcong > 90) {
      insights.push({
        icon: 'fas fa-trophy',
        color: 'text-green-500',
        title: 'Hiệu suất xuất sắc',
        content: `Tỷ lệ thành công ${summary.ty_le_thanhcong}% rất tốt. Duy trì chất lượng dịch vụ!`,
      });
    }

    // Insight 2: Đơn hàng hủy
    if (summary.tong_donhang_huy > summary.tong_donhang * 0.1) {
      insights.push({
        icon: 'fas fa-times-circle',
        color: 'text-red-500',
        title: 'Nhiều đơn bị hủy',
        content: `Có ${summary.tong_donhang_huy} đơn bị hủy (${((summary.tong_donhang_huy / summary.tong_donhang) * 100).toFixed(1)}%). Cần phân tích nguyên nhân.`,
      });
    }

    // Insight 3: Giá trị đơn hàng trung bình
    if (summary.avg_gia_tri_donhang) {
      const avgValue = summary.avg_gia_tri_donhang;
      if (avgValue < 50000) {
        insights.push({
          icon: 'fas fa-shopping-basket',
          color: 'text-blue-500',
          title: 'Giá trị đơn thấp',
          content: `Giá trị đơn trung bình ${avgValue.toFixed(0)}đ. Xem xét upsell hoặc combo để tăng giá trị đơn.`,
        });
      } else if (avgValue > 150000) {
        insights.push({
          icon: 'fas fa-chart-line',
          color: 'text-purple-500',
          title: 'Giá trị đơn cao',
          content: `Giá trị đơn trung bình ${avgValue.toFixed(0)}đ rất tốt. Tiếp tục chiến lược hiện tại.`,
        });
      }
    }
  }

  // Insight 4: Xu hướng thời gian
  if (data.length > 1) {
    const firstMonth = data[0];
    const lastMonth = data[data.length - 1];

    if (firstMonth && lastMonth) {
      const growth = (
        ((lastMonth.tong_donhang - firstMonth.tong_donhang) /
          firstMonth.tong_donhang) *
        100
      ).toFixed(1);

      if (Math.abs(growth) > 10) {
        insights.push({
          icon: growth > 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down',
          color: growth > 0 ? 'text-green-500' : 'text-red-500',
          title: growth > 0 ? 'Tăng trưởng tốt' : 'Sụt giảm đáng kể',
          content: `Số đơn hàng ${growth > 0 ? 'tăng' : 'giảm'} ${Math.abs(growth)}% so với đầu kỳ.`,
        });
      }
    }
  }

  // Thêm insights mặc định nếu không có
  if (insights.length === 0) {
    insights = [
      {
        icon: 'fas fa-info-circle',
        color: 'text-blue-500',
        title: 'Phân tích dữ liệu',
        content:
          'Dữ liệu đang được thu thập và phân tích. Quay lại sau để xem insights chi tiết.',
      },
      {
        icon: 'fas fa-chart-pie',
        color: 'text-purple-500',
        title: 'Đề xuất',
        content:
          'Sử dụng các bộ lọc khác nhau để có cái nhìn toàn diện về hiệu suất đơn hàng.',
      },
      {
        icon: 'fas fa-bullseye',
        color: 'text-[#e8956f]',
        title: 'Mục tiêu',
        content:
          'Đặt mục tiêu tăng trưởng 15% mỗi quý và giảm tỷ lệ hủy đơn xuống dưới 5%.',
      },
    ];
  }

  container.innerHTML = insights
    .map(
      (insight) => `
        <div class="card">
            <div class="p-6">
                <div class="flex items-start space-x-3">
                    <div class="${insight.color} text-xl">
                        <i class="${insight.icon}"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-[#1c140d] dark:text-white">${insight.title}</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">${insight.content}</p>
                    </div>
                </div>
            </div>
        </div>
    `,
    )
    .join('');
}

// Format tiền tệ
function formatCurrency(amount) {
  if (!amount) return '0 đ';
  return new Intl.NumberFormat('vi-VN', {
    style: 'currency',
    currency: 'VND',
    minimumFractionDigits: 0,
  }).format(amount);
}

// Format ngày tháng
function formatDate(dateString) {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  });
}

// Format trạng thái đơn hàng
function formatOrderStatus(status) {
  const statusMap = {
    cho_xac_nhan: {
      text: 'Chờ xác nhận',
      class:
        'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-500',
    },
    da_xac_nhan: {
      text: 'Đã xác nhận',
      class: 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300',
    },
    dang_giao: {
      text: 'Đang giao',
      class:
        'bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300',
    },
    da_giao: {
      text: 'Đã giao',
      class:
        'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300',
    },
    da_huy: {
      text: 'Đã hủy',
      class: 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300',
    },
  };

  const info = statusMap[status] || {
    text: status,
    class: 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300',
  };
  return `<span class="px-2 py-1 text-xs font-medium rounded-full ${info.class}">${info.text}</span>`;
}

// Format phương thức thanh toán
function formatPaymentMethod(method) {
  const methodMap = {
    tien_mat: 'Tiền mặt',
    the_ngan_hang: 'Thẻ ngân hàng',
    vi_dien_tu: 'Ví điện tử',
  };
  return methodMap[method] || method;
}

function getPaymentMethodClass(method) {
  const classMap = {
    tien_mat:
      'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300',
    the_ngan_hang:
      'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300',
    vi_dien_tu:
      'bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300',
  };
  return (
    classMap[method] ||
    'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300'
  );
}

// Hiển thị loading
function showLoading() {
  const modal = document.getElementById('loadingModal');
  if (modal) modal.classList.remove('hidden');
}

// Ẩn loading
function hideLoading() {
  const modal = document.getElementById('loadingModal');
  if (modal) modal.classList.add('hidden');
}

// Hiển thị lỗi
function showError(message) {
  alert('Lỗi: ' + message);
}

// Xuất Excel (mẫu)
document.getElementById('btnXuatExcel')?.addEventListener('click', function () {
  if (currentData.length === 0) {
    alert('Không có dữ liệu để xuất');
    return;
  }

  // Tạo một bảng tạm thời để xuất
  const headers = [
    'Mã đơn',
    'Ngày đặt',
    'Khách hàng',
    'Số món',
    'Tổng tiền',
    'PTTT',
    'Trạng thái',
  ];
  const csvContent = [
    headers.join(','),
    ...currentData.map((item) =>
      [
        item.donhang_id || '',
        item.ngay_tao || '',
        item.hoten || '',
        item.so_mon || '',
        item.tong_cuoi_cung || '',
        formatPaymentMethod(item.phuong_thuc_thanhtoan || ''),
        formatOrderStatus(item.trang_thai_donhang || '').replace(
          /<[^>]*>/g,
          '',
        ),
      ].join(','),
    ),
  ].join('\n');

  // Tạo blob và download
  const blob = new Blob(['\uFEFF' + csvContent], {
    type: 'text/csv;charset=utf-8;',
  });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = `bao_cao_don_hang_${new Date().toISOString().slice(0, 10)}.csv`;
  link.click();

  alert('Đang tải file Excel...');
});

// Xuất PDF (mẫu)
document.getElementById('btnXuatPDF')?.addEventListener('click', function () {
  alert(
    'Chức năng xuất PDF đang được phát triển. Vui lòng sử dụng chức năng xuất Excel tạm thời.',
  );
});

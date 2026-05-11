// Main App
class DashBeautyApp {
  constructor() {
    this.currentPage = "home";
    this.selectedBusiness = null;
    this.selectedService = null;
  }

  init() {
    // Hide loading screen
    setTimeout(() => {
      document.getElementById("loading-screen").style.display = "none";
      document.getElementById("main-content").style.display = "block";
    }, 1000);

    // Setup event listeners
    this.setupEventListeners();

    // Check authentication
    authManager.checkAuth();

    // Load initial data
    this.loadBusinesses();

    // Setup PWA
    this.setupPWA();
  }

  setupEventListeners() {
    // Navigation
    document.querySelectorAll(".nav-link").forEach((link) => {
      link.addEventListener("click", (e) => {
        e.preventDefault();
        const page = link.dataset.page;
        this.navigateTo(page);
      });
    });

    // Search
    document.getElementById("search-btn").addEventListener("click", () => {
      const query = document.getElementById("search-input").value;
      if (query) {
        this.searchBusinesses(query);
      } else {
        this.loadBusinesses();
      }
    });

    document
      .getElementById("search-input")
      .addEventListener("keypress", (e) => {
        if (e.key === "Enter") {
          const query = e.target.value;
          if (query) {
            this.searchBusinesses(query);
          } else {
            this.loadBusinesses();
          }
        }
      });

    // Categories
    document.querySelectorAll(".category-card").forEach((card) => {
      card.addEventListener("click", () => {
        const category = card.dataset.category;
        this.searchBusinesses(category);
      });
    });

    // Auth buttons
    document.getElementById("login-btn").addEventListener("click", () => {
      this.showLoginModal();
    });

    document.getElementById("register-btn").addEventListener("click", () => {
      this.showRegisterModal();
    });

    document.getElementById("logout-btn").addEventListener("click", () => {
      authManager.logout();
    });

    // Modal close buttons
    document.querySelectorAll(".close").forEach((close) => {
      close.addEventListener("click", () => {
        this.closeModals();
      });
    });

    // Switch between login/register
    document
      .getElementById("switch-to-register")
      .addEventListener("click", (e) => {
        e.preventDefault();
        this.closeModals();
        this.showRegisterModal();
      });

    document
      .getElementById("switch-to-login")
      .addEventListener("click", (e) => {
        e.preventDefault();
        this.closeModals();
        this.showLoginModal();
      });

    // Login form
    document
      .getElementById("login-form")
      .addEventListener("submit", async (e) => {
        e.preventDefault();
        const email = document.getElementById("login-email").value;
        const password = document.getElementById("login-password").value;
        await authManager.login(email, password);
      });

    // Register form
    document
      .getElementById("register-form")
      .addEventListener("submit", async (e) => {
        e.preventDefault();
        const userData = {
          name: document.getElementById("register-name").value,
          email: document.getElementById("register-email").value,
          phone: document.getElementById("register-phone").value,
          password: document.getElementById("register-password").value,
          user_type: document.getElementById("register-type").value,
        };
        await authManager.register(userData);
      });

    // Modal click outside to close
    window.addEventListener("click", (e) => {
      if (e.target.classList.contains("modal")) {
        this.closeModals();
      }
    });
  }

  navigateTo(page) {
    // Update active nav
    document.querySelectorAll(".nav-link").forEach((link) => {
      link.classList.remove("active");
      if (link.dataset.page === page) {
        link.classList.add("active");
      }
    });

    // Update pages
    document.querySelectorAll(".page").forEach((p) => {
      p.classList.remove("active");
    });

    const pageElement = document.getElementById(`page-${page}`);
    if (pageElement) {
      pageElement.classList.add("active");
      this.currentPage = page;

      // Load page data
      if (page === "appointments") {
        this.loadAppointments();
      } else if (page === "profile") {
        this.loadProfile();
      }
    }
  }

  async loadBusinesses() {
    const result = await API.getBusinesses();
    if (result.success) {
      this.renderBusinesses(result.data);
    } else {
      this.showToast("Erro ao carregar estabelecimentos", "error");
    }
  }

  async searchBusinesses(query) {
    const result = await API.searchBusinesses(query);
    if (result.success) {
      this.renderBusinesses(result.data);
    } else {
      this.showToast("Nenhum resultado encontrado", "warning");
    }
  }

  renderBusinesses(businesses) {
    const container = document.getElementById("businesses-list");

    if (!businesses || businesses.length === 0) {
      container.innerHTML =
        '<div class="empty-state">Nenhum estabelecimento encontrado</div>';
      return;
    }

    container.innerHTML = businesses
      .map(
        (business) => `
            <div class="business-card" data-id="${business.id}">
                <div class="business-image">
                    <i class="fas fa-store"></i>
                    ${business.is_featured ? '<span class="featured-badge"><i class="fas fa-star"></i> Destaque</span>' : ""}
                </div>
                <div class="business-info">
                    <h4>${business.business_name}</h4>
                    <div class="business-rating">
                        <div class="stars">${this.renderStars(business.avg_rating || 0)}</div>
                        <span>(${business.total_ratings || 0})</span>
                    </div>
                    <div class="business-address">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>${business.address || "Endereço não informado"}</span>
                    </div>
                    <span class="business-category">Salão de Beleza</span>
                </div>
            </div>
        `,
      )
      .join("");

    // Add click event to business cards
    document.querySelectorAll(".business-card").forEach((card) => {
      card.addEventListener("click", () => {
        const id = card.dataset.id;
        this.showBusinessDetail(id);
      });
    });
  }

  renderStars(rating) {
    const fullStars = Math.floor(rating);
    const hasHalfStar = rating % 1 >= 0.5;
    let stars = "";

    for (let i = 0; i < fullStars; i++) {
      stars += '<i class="fas fa-star"></i>';
    }
    if (hasHalfStar) {
      stars += '<i class="fas fa-star-half-alt"></i>';
    }
    for (let i = stars.length / 2; i < 5; i++) {
      stars += '<i class="far fa-star"></i>';
    }

    return stars;
  }

  async showBusinessDetail(id) {
    const result = await API.getBusiness(id);
    if (result.success) {
      this.selectedBusiness = result.data;
      this.renderBusinessDetail(result.data);
      this.navigateTo("business-detail");
    }
  }

  renderBusinessDetail(business) {
    const container = document.getElementById("page-business-detail");

    container.innerHTML = `
            <div class="business-detail">
                <div class="business-detail-header">
                    <button class="btn-back" onclick="app.goBack()">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </button>
                    <h2>${business.business_name}</h2>
                    <div class="business-detail-rating">
                        <div class="stars">${this.renderStars(business.avg_rating || 0)}</div>
                        <span>${business.avg_rating?.toFixed(1) || 0} (${business.total_ratings || 0} avaliações)</span>
                    </div>
                    <p class="business-detail-address">
                        <i class="fas fa-map-marker-alt"></i> ${business.address || "Endereço não informado"}
                    </p>
                    <p class="business-detail-description">${business.description || ""}</p>
                </div>
                
                <div class="services-section">
                    <h3>Serviços</h3>
                    <div class="services-list">
                        ${
                          business.services && business.services.length > 0
                            ? business.services
                                .map(
                                  (service) => `
                                <div class="service-card" data-id="${service.id}">
                                    <div class="service-info">
                                        <h4>${service.name}</h4>
                                        <p>${service.description || ""}</p>
                                        <div class="service-price">R$ ${parseFloat(service.price).toFixed(2)}</div>
                                        <div class="service-duration"><i class="far fa-clock"></i> ${service.duration_minutes} min</div>
                                    </div>
                                    <button class="btn-book" onclick="app.bookService(${service.id})">Agendar</button>
                                </div>
                            `,
                                )
                                .join("")
                            : "<p>Nenhum serviço disponível</p>"
                        }
                    </div>
                </div>
                
                <div class="hours-section">
                    <h3>Horário de Funcionamento</h3>
                    <div class="hours-list">
                        ${this.renderHours(business.hours)}
                    </div>
                </div>
                
                <div class="reviews-section">
                    <h3>Avaliações</h3>
                    <div class="reviews-list">
                        ${
                          business.reviews && business.reviews.length > 0
                            ? business.reviews
                                .map(
                                  (review) => `
                                <div class="review-card">
                                    <div class="review-header">
                                        <strong>${review.client_name}</strong>
                                        <div class="stars">${this.renderStars(review.rating)}</div>
                                    </div>
                                    <p>${review.comment}</p>
                                    <small>${new Date(review.created_at).toLocaleDateString("pt-BR")}</small>
                                </div>
                            `,
                                )
                                .join("")
                            : "<p>Seja o primeiro a avaliar este estabelecimento</p>"
                        }
                    </div>
                </div>
            </div>
        `;

    container.classList.add("active");
  }

  renderHours(hours) {
    const days = [
      "Domingo",
      "Segunda",
      "Terça",
      "Quarta",
      "Quinta",
      "Sexta",
      "Sábado",
    ];

    if (!hours || hours.length === 0) {
      return "<p>Horários não informados</p>";
    }

    return hours
      .map(
        (hour) => `
            <div class="hour-item">
                <span class="day">${days[hour.day_of_week]}</span>
                <span class="time">${hour.is_closed ? "Fechado" : `${hour.open_time?.substring(0, 5)} - ${hour.close_time?.substring(0, 5)}`}</span>
            </div>
        `,
      )
      .join("");
  }

  async bookService(serviceId) {
    if (!authManager.currentUser) {
      this.showToast("Faça login para agendar", "warning");
      this.showLoginModal();
      return;
    }

    const service = this.selectedBusiness.services.find(
      (s) => s.id == serviceId,
    );
    this.selectedService = service;

    // Show date selection
    const today = new Date().toISOString().split("T")[0];
    const minDate = today;
    const maxDate = new Date();
    maxDate.setDate(maxDate.getDate() + 30);

    const date = prompt("Digite a data (AAAA-MM-DD):", today);
    if (!date) return;

    // Load available times
    const timesResult = await API.getAvailableTimes(
      this.selectedBusiness.id,
      date,
      serviceId,
    );

    if (!timesResult.success || timesResult.data.length === 0) {
      this.showToast("Nenhum horário disponível nesta data", "error");
      return;
    }

    const timeList = timesResult.data.join("\n");
    const time = prompt(
      `Horários disponíveis:\n${timeList}\n\nDigite o horário desejado (HH:MM):`,
    );

    if (!time) return;

    // Create appointment
    const appointmentData = {
      service_id: serviceId,
      appointment_date: date,
      appointment_time: `${time}:00`,
      price: service.price,
      notes: "",
    };

    const result = await API.createAppointment(appointmentData);

    if (result.success) {
      this.showToast("Agendamento realizado com sucesso!", "success");
      this.navigateTo("appointments");
      this.loadAppointments();
    } else {
      this.showToast(result.message || "Erro ao agendar", "error");
    }
  }

  async loadAppointments() {
    const result = await API.getAppointments();
    if (result.success && result.data) {
      this.renderAppointments(result.data);
    }
  }

  renderAppointments(appointments) {
    const container = document.getElementById("appointments-list");

    if (!appointments || appointments.length === 0) {
      container.innerHTML =
        '<div class="empty-state">Você não tem agendamentos</div>';
      return;
    }

    container.innerHTML = appointments
      .map(
        (app) => `
            <div class="appointment-card">
                <div class="appointment-info">
                    <h4>${app.service_name}</h4>
                    <p>${app.business_name}</p>
                    <p><i class="far fa-calendar-alt"></i> ${new Date(app.appointment_date).toLocaleDateString("pt-BR")} às ${app.appointment_time?.substring(0, 5)}</p>
                    <p><strong>R$ ${parseFloat(app.price).toFixed(2)}</strong></p>
                    <span class="appointment-status status-${app.status}">${this.getStatusText(app.status)}</span>
                </div>
                ${
                  app.status === "scheduled"
                    ? `
                    <button class="btn-cancel" onclick="app.cancelAppointment(${app.id})">Cancelar</button>
                `
                    : ""
                }
            </div>
        `,
      )
      .join("");
  }

  async cancelAppointment(id) {
    if (confirm("Tem certeza que deseja cancelar este agendamento?")) {
      const result = await API.cancelAppointment(id);
      if (result.success) {
        this.showToast("Agendamento cancelado", "success");
        this.loadAppointments();
      } else {
        this.showToast("Erro ao cancelar", "error");
      }
    }
  }

  async loadProfile() {
    if (authManager.currentUser) {
      // Load statistics
      const appointments = await API.getAppointments();
      if (appointments.success) {
        document.getElementById("total-appointments").textContent =
          appointments.data?.length || 0;
      }
    }
  }

  getStatusText(status) {
    const statusMap = {
      scheduled: "Agendado",
      confirmed: "Confirmado",
      completed: "Concluído",
      cancelled: "Cancelado",
    };
    return statusMap[status] || status;
  }

  showLoginModal() {
    document.getElementById("login-modal").style.display = "flex";
  }

  showRegisterModal() {
    document.getElementById("register-modal").style.display = "flex";
  }

  closeModals() {
    document.querySelectorAll(".modal").forEach((modal) => {
      modal.style.display = "none";
    });
  }

  goBack() {
    this.navigateTo("home");
    document.getElementById("page-business-detail").classList.remove("active");
  }

  showToast(message, type = "info") {
    const toast = document.getElementById("toast");
    toast.textContent = message;
    toast.className = `toast ${type}`;
    toast.style.display = "block";

    setTimeout(() => {
      toast.style.display = "none";
    }, 3000);
  }

  setupPWA() {
    let deferredPrompt;

    window.addEventListener("beforeinstallprompt", (e) => {
      e.preventDefault();
      deferredPrompt = e;
      document.getElementById("install-btn").style.display = "flex";
    });

    document
      .getElementById("install-btn")
      .addEventListener("click", async () => {
        if (deferredPrompt) {
          deferredPrompt.prompt();
          const result = await deferredPrompt.userChoice;
          if (result.outcome === "accepted") {
            console.log("Usuário aceitou instalar o PWA");
          }
          deferredPrompt = null;
          document.getElementById("install-btn").style.display = "none";
        }
      });

    // Register Service Worker
    if ("serviceWorker" in navigator) {
      navigator.serviceWorker
        .register("/sw.js")
        .then((reg) => console.log("Service Worker registrado", reg))
        .catch((err) => console.error("Erro ao registrar Service Worker", err));
    }
  }
}

// Initialize app
const app = new DashBeautyApp();
window.app = app;

// Start app when DOM is ready
document.addEventListener("DOMContentLoaded", () => {
  app.init();
});

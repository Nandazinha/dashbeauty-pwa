// ============================================
// DASHBEAUTY PWA - API SERVICE
// ============================================

const API = {
  baseUrl: "/api",
  token: null,

  init() {
    this.token = localStorage.getItem("auth_token");
  },

  setToken(token) {
    this.token = token;
    if (token) {
      localStorage.setItem("auth_token", token);
    } else {
      localStorage.removeItem("auth_token");
    }
  },

  getHeaders() {
    const headers = {
      "Content-Type": "application/json",
    };
    if (this.token) {
      headers["Authorization"] = `Bearer ${this.token}`;
    }
    return headers;
  },

  async request(endpoint, method = "GET", data = null) {
    const url = `${this.baseUrl}/${endpoint}`;
    const options = {
      method,
      headers: this.getHeaders(),
    };

    if (data && (method === "POST" || method === "PUT")) {
      options.body = JSON.stringify(data);
    }

    try {
      const response = await fetch(url, options);
      const result = await response.json();

      if (response.status === 401) {
        this.setToken(null);
        if (window.location.pathname !== "/login.html") {
          window.location.href = "/login.html";
        }
      }

      return result;
    } catch (error) {
      console.error("API Error:", error);
      return { success: false, message: "Erro de conexão com o servidor" };
    }
  },

  // Auth
  async login(email, password) {
    return this.request("auth/login", "POST", { email, password });
  },

  async register(userData) {
    return this.request("auth/register", "POST", userData);
  },

  async getMe() {
    return this.request("auth/me", "GET");
  },

  // Businesses
  async getBusinesses() {
    return this.request("businesses", "GET");
  },

  async getBusiness(id) {
    return this.request(`businesses/${id}`, "GET");
  },

  async searchBusinesses(query) {
    return this.request(
      `businesses?search=${encodeURIComponent(query)}`,
      "GET",
    );
  },

  async createBusiness(data) {
    return this.request("businesses", "POST", data);
  },

  async updateBusiness(id, data) {
    return this.request(`businesses/${id}`, "PUT", data);
  },

  // Services
  async getServices(businessId) {
    return this.request(`services?business_id=${businessId}`, "GET");
  },

  async createService(data) {
    return this.request("services", "POST", data);
  },

  async updateService(id, data) {
    return this.request(`services/${id}`, "PUT", data);
  },

  async deleteService(id) {
    return this.request(`services/${id}`, "DELETE");
  },

  // Appointments
  async getAppointments() {
    return this.request("appointments", "GET");
  },

  async createAppointment(data) {
    return this.request("appointments", "POST", data);
  },

  async updateAppointmentStatus(id, status) {
    return this.request(`appointments/${id}/status`, "PUT", { status });
  },

  async cancelAppointment(id) {
    return this.request(`appointments/${id}`, "DELETE");
  },

  async getAvailableTimes(businessId, date, serviceId) {
    return this.request(
      `appointments/${businessId}/available-times?date=${date}&service_id=${serviceId}`,
      "GET",
    );
  },

  // Business Hours
  async getBusinessHours(businessId) {
    return this.request(`businesses/${businessId}/hours`, "GET");
  },

  async updateBusinessHours(businessId, hours) {
    return this.request(`businesses/${businessId}/hours`, "PUT", { hours });
  },

  // Reviews
  async getReviews(businessId) {
    return this.request(`reviews?business_id=${businessId}`, "GET");
  },

  async createReview(data) {
    return this.request("reviews", "POST", data);
  },
};

// Inicializar API
API.init();

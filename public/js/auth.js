// ============================================
// DASHBEAUTY PWA - AUTH SERVICE
// ============================================

const Auth = {
  currentUser: null,

  async init() {
    await this.checkAuth();
    this.updateUI();
  },

  async checkAuth() {
    const token = localStorage.getItem("auth_token");
    if (!token) {
      this.currentUser = null;
      return false;
    }

    try {
      const response = await API.getMe();
      if (response.success) {
        this.currentUser = response.data;
        return true;
      } else {
        this.logout();
        return false;
      }
    } catch (error) {
      this.logout();
      return false;
    }
  },

  async login(email, password) {
    const result = await API.login(email, password);
    if (result.success) {
      API.setToken(result.data.token);
      this.currentUser = result.data;
      this.updateUI();
      return { success: true, userType: result.data.user_type };
    }
    return { success: false, message: result.message };
  },

  async register(userData) {
    const result = await API.register(userData);
    if (result.success) {
      API.setToken(result.data.token);
      this.currentUser = result.data;
      this.updateUI();
      return { success: true, userType: result.data.user_type };
    }
    return { success: false, message: result.message };
  },

  logout() {
    API.setToken(null);
    this.currentUser = null;
    this.updateUI();
    window.location.href = "/login.html";
  },

  updateUI() {
    const isLoggedIn = this.currentUser !== null;
    const userType = this.currentUser?.user_type;

    // Atualizar elementos da UI
    const authButtons = document.querySelector(".auth-buttons");
    const userInfo = document.querySelector(".user-info");

    if (authButtons && userInfo) {
      if (isLoggedIn) {
        authButtons.style.display = "none";
        userInfo.style.display = "flex";
      } else {
        authButtons.style.display = "flex";
        userInfo.style.display = "none";
      }
    }

    // Atualizar nome do usuário
    const userNameSpan = document.getElementById("user-name");
    if (userNameSpan && this.currentUser) {
      userNameSpan.textContent = this.currentUser.name;
    }

    // Redirecionar baseado no tipo
    if (isLoggedIn && window.location.pathname === "/") {
      if (userType === "business") {
        window.location.href = "/business-dashboard.html";
      } else {
        window.location.href = "/client-dashboard.html";
      }
    }
  },

  requireAuth() {
    if (!this.currentUser) {
      window.location.href = "/login.html";
      return false;
    }
    return true;
  },

  requireBusiness() {
    if (!this.requireAuth()) return false;
    if (this.currentUser.user_type !== "business") {
      window.location.href = "/client-dashboard.html";
      return false;
    }
    return true;
  },

  requireClient() {
    if (!this.requireAuth()) return false;
    if (this.currentUser.user_type !== "client") {
      window.location.href = "/business-dashboard.html";
      return false;
    }
    return true;
  },
};

// Inicializar Auth quando a página carregar
document.addEventListener("DOMContentLoaded", () => {
  Auth.init();
});

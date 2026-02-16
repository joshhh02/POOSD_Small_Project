/* Authentication and Cookie Management */
async function doLogin() {
  userId = 0;
  firstName = "";
  lastName = "";

  const login = document.getElementById("loginName").value.trim();
  const password = document.getElementById("loginPassword").value;

  const resultEl = document.getElementById("loginResult");
  if (resultEl) resultEl.textContent = "";

  const hash = md5(password);

  try {
    const data = await apiRequest("LoginContact", { login, password: hash });

    userId = data.id || 0;
    if (userId < 1) {
      if (resultEl) resultEl.textContent = "Invalid username or password";
      return;
    }

    firstName = data.firstName || "";
    lastName = data.lastName || "";

    saveCookie();
    window.location.href = "contact_management.html";
  } catch (err) {
    if (resultEl) resultEl.textContent = err.message;
  }
}

async function doSignUp() {
  const first = document.getElementById("signupFirstName").value.trim();
  const last = document.getElementById("signupLastName").value.trim();
  const login = document.getElementById("signupLogin").value.trim();
  const password = document.getElementById("signupPassword").value;

  const resultEl = document.getElementById("signupResult");
  if (resultEl) resultEl.textContent = "";

  const hash = md5(password);

  try {
    const data = await apiRequest("RegisterContact", {
      firstName: first,
      lastName: last,
      login,
      password: hash,
    });

    // RegisterContact.php returns id + firstName + lastName
    userId = data.id || 0;
    firstName = data.firstName || first;
    lastName = data.lastName || last;

    if (userId > 0) {
      saveCookie();
      window.location.href = "contact_management.html";
    } else {
      if (resultEl) resultEl.textContent = "Registration failed";
    }
  } catch (err) {
    if (resultEl) resultEl.textContent = err.message;
  }
}

function doLogout() {
  userId = 0;
  firstName = "";
  lastName = "";
  clearCookie();
  window.location.href = "start-up-page.html"; // or index.html later
}

// ----- cookies -----
function saveCookie() {
  const minutes = 20;
  const date = new Date();
  date.setTime(date.getTime() + minutes * 60 * 1000);

  document.cookie =
    "firstName=" + encodeURIComponent(firstName) +
    ",lastName=" + encodeURIComponent(lastName) +
    ",userId=" + userId +
    ";expires=" + date.toUTCString() +
    ";path=/";
}

function readCookie() {
  userId = -1;

  const data = document.cookie.split(",");
  for (let i = 0; i < data.length; i++) {
    const tokens = data[i].trim().split("=");

    if (tokens[0] === "firstName") firstName = decodeURIComponent(tokens[1] || "");
    else if (tokens[0] === "lastName") lastName = decodeURIComponent(tokens[1] || "");
    else if (tokens[0] === "userId") userId = parseInt((tokens[1] || "").trim(), 10);
  }

  if (!Number.isFinite(userId) || userId < 0) {
    window.location.href = "start-up-page.html";
  } else {
    const userNameEl = document.getElementById("userName");
    if (userNameEl) userNameEl.textContent = `Logged in as ${firstName} ${lastName}`;
  }
}

function clearCookie() {
  document.cookie = "firstName=; expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/";
  document.cookie = "lastName=; expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/";
  document.cookie = "userId=; expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/";
}

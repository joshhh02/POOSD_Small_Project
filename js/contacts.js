let searchTimer = null;

function initContactsPage() {
  readCookie();

  const searchEl = document.getElementById("searchText");
  if (searchEl) {
    searchEl.addEventListener("input", () => debounceSearch(searchEl.value));
  }

  debounceSearch("");
}

function debounceSearch(q) {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => searchContacts(q.trim()), 350);
}

async function searchContacts(query) {
  const msg = document.getElementById("contactSearchResult");
  if (msg) msg.textContent = "";

  try {
    const data = await apiRequest("SearchContacts", { search: query, userId });
    renderContacts(data.results || []);
    if (msg) msg.textContent = "Contacts retrieved";
  } catch (err) {
    if (msg) msg.textContent = err.message;
    renderContacts([]);
  }
}

async function addContact() {
  const name = document.getElementById("contactName")?.value.trim() || "";
  const phone = document.getElementById("contactPhone")?.value.trim() || "";
  const email = document.getElementById("contactEmail")?.value.trim() || "";

  const msg = document.getElementById("contactAddResult");
  if (msg) msg.textContent = "";

  try {
    await apiRequest("AddContact", { name, phone, email, userId });
    if (msg) msg.textContent = "Contact added";
    searchContacts(document.getElementById("searchText")?.value.trim() || "");
  } catch (err) {
    if (msg) msg.textContent = err.message;
  }
}

async function updateContact(id) {
  const name = prompt("New name:");
  if (name === null) return;
  const phone = prompt("New phone:");
  if (phone === null) return;
  const email = prompt("New email:");
  if (email === null) return;

  try {
    await apiRequest("UpdateContact", { id, name: name.trim(), phone: phone.trim(), email: email.trim(), userId });
    searchContacts(document.getElementById("searchText")?.value.trim() || "");
  } catch (err) {
    alert(err.message);
  }
}

async function deleteContact(id) {
  if (!confirm("Delete this contact?")) return;

  try {
    await apiRequest("DeleteContact", { id, userId });
    searchContacts(document.getElementById("searchText")?.value.trim() || "");
  } catch (err) {
    alert(err.message);
  }
}

function renderContacts(results) {
  const body = document.getElementById("contactsBody");
  const list = document.getElementById("contactList");

  if (body) {
    body.innerHTML = "";
    for (const c of results) {
      const tr = document.createElement("tr");
      tr.innerHTML = `
        <td>${escapeHtml(c.name || "")}</td>
        <td>${escapeHtml(c.phone || "")}</td>
        <td>${escapeHtml(c.email || "")}</td>
        <td>
          <button type="button" onclick="updateContact(${Number(c.id)})">Edit</button>
          <button type="button" onclick="deleteContact(${Number(c.id)})">Delete</button>
        </td>
      `;
      body.appendChild(tr);
    }
    return;
  }

  if (list) {
    list.innerHTML = results.map(c => `
      <div>
        <b>${escapeHtml(c.name || "")}</b><br/>
        ${escapeHtml(c.phone || "")}<br/>
        ${escapeHtml(c.email || "")}<br/>
        <button type="button" onclick="updateContact(${Number(c.id)})">Edit</button>
        <button type="button" onclick="deleteContact(${Number(c.id)})">Delete</button>
      </div><hr/>
    `).join("");
  }
}

function escapeHtml(s) {
  return String(s)
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}

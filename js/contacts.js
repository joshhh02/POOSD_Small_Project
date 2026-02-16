function initContactsPage() {
  readCookie();

  const searchEl = document.getElementById("searchText");
  if (searchEl) {
    searchEl.addEventListener("input", () => debounceSearch(searchEl.value));
  }

  debounceSearch(""); // load all contacts
}

function debounceSearch(q) {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => searchContacts(q.trim()), 300);
}

async function searchContacts(query) {
  const msg = document.getElementById("contactSearchResult");
  if (msg) msg.textContent = "";

  try {
    // SearchContact.php expects { search, userId }
    const data = await apiRequest("SearchContact", { search: query, userId });
    renderContacts(data.results || []);
    if (msg) msg.textContent = "Contacts retrieved";
  } catch (err) {
    if (msg) msg.textContent = err.message;
    renderContacts([]);
  }
}

async function addContact() {
  const full = document.getElementById("contactName")?.value.trim() || "";
  const phone = document.getElementById("contactPhone")?.value.trim() || "";
  const email = document.getElementById("contactEmail")?.value.trim() || "";

  const msg = document.getElementById("contactAddResult");
  if (msg) msg.textContent = "";

  const { first, last } = splitName(full);

  try {
    // addContact.php expects firstName,lastName,email,phone,userId
    await apiRequest("addContact", {
      firstName: first,
      lastName: last,
      email,
      phone,
      userId,
    });

    if (msg) msg.textContent = "Contact added";
    searchContacts(document.getElementById("searchText")?.value.trim() || "");
  } catch (err) {
    if (msg) msg.textContent = err.message;
  }
}

async function editContact(contactId) {
  const firstName = prompt("First name:");
  if (firstName === null) return;

  const lastName = prompt("Last name:");
  if (lastName === null) return;

  const phone = prompt("Phone:");
  if (phone === null) return;

  const email = prompt("Email:");
  if (email === null) return;

  try {
    // editContact.php expects contactId,firstName,lastName,email,phone,userId
    await apiRequest("editContact", {
      contactId,
      firstName: firstName.trim(),
      lastName: lastName.trim(),
      email: email.trim(),
      phone: phone.trim(),
      userId,
    });

    searchContacts(document.getElementById("searchText")?.value.trim() || "");
  } catch (err) {
    alert(err.message);
  }
}

async function deleteContact(contactId) {
  if (!confirm("Delete this contact?")) return;

  try {
    // deleteContact.php expects { contactId }
    await apiRequest("deleteContact", { contactId });
    searchContacts(document.getElementById("searchText")?.value.trim() || "");
  } catch (err) {
    alert(err.message);
  }
}

function renderContacts(results) {
  const body = document.getElementById("contactsBody");
  if (!body) return;

  body.innerHTML = "";

  for (const c of results) {
    // SearchContact.php returns ID/Name/Phone/Email with this casing
    const id = Number(c.ID);

    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td>${escapeHtml(c.Name || "")}</td>
      <td>${escapeHtml(c.Phone || "")}</td>
      <td>${escapeHtml(c.Email || "")}</td>
      <td>
        <button type="button" onclick="editContact(${id})">Edit</button>
        <button type="button" onclick="deleteContact(${id})">Delete</button>
      </td>
    `;
    body.appendChild(tr);
  }
}

function splitName(full) {
  const parts = full.split(/\s+/).filter(Boolean);
  if (parts.length === 0) return { first: "", last: "" };
  if (parts.length === 1) return { first: parts[0], last: "" };
  return { first: parts[0], last: parts.slice(1).join(" ") };
}

function escapeHtml(s) {
  return String(s)
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}
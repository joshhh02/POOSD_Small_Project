/* Fetch Wrapper*/ 
async function apiRequest(endpoint, payload = {}, method = "POST") {
  const url = `${urlBase}/${endpoint}.${extension}`;

  const res = await fetch(url, {
    method,
    headers: { "Content-Type": "application/json; charset=UTF-8" },
    body: method === "GET" ? null : JSON.stringify(payload),
  });

  let data;
  try {
    data = await res.json();
  } catch {
    throw new Error("Server did not return JSON.");
  }

  if (!res.ok) {
    throw new Error(data?.message || "Request failed.");
  }

  return data;
}

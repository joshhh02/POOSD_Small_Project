/* Fetch Wrapper*/ 
async function apiRequest(endpointFileNoExt, payload = {}) {
  const url = `${urlBase}/${endpointFileNoExt}.${extension}`;

  const res = await fetch(url, {
    method: "POST",
    headers: { "Content-Type": "application/json; charset=UTF-8" },
    body: JSON.stringify(payload),
  });

  let data;
  try {
    data = await res.json();
  } catch {
    throw new Error("Server did not return JSON.");
  }

  // If server returns an HTTP error
  if (!res.ok) {
    throw new Error(data?.error || "Request failed.");
  }

  // Most of your PHP returns an "error" string on failure
  if (data && typeof data.error === "string" && data.error.length > 0) {
    throw new Error(data.error);
  }

  // Some endpoints also include success:false
  if (data && data.success === false) {
    throw new Error(data.error || "Operation failed.");
  }

  return data;
}

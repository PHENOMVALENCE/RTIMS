// Tab functionality
function showTab(tabName) {
  // Hide all tab contents
  const tabContents = document.querySelectorAll(".tab-content")
  tabContents.forEach((content) => {
    content.classList.remove("active")
  })

  // Remove active class from all tab buttons
  const tabButtons = document.querySelectorAll(".tab-button")
  tabButtons.forEach((button) => {
    button.classList.remove("active")
  })

  // Show selected tab content
  document.getElementById(tabName).classList.add("active")

  // Add active class to clicked button
  event.target.classList.add("active")
}

// Update login fields based on user type
function updateLoginFields() {
  const userType = document.getElementById("user_type").value
  const usernameLabel = document.getElementById("username-label")
  const usernameInput = document.getElementById("username")

  switch (userType) {
    case "user":
      usernameLabel.textContent = "Driving Licence Number:"
      usernameInput.placeholder = "Enter your driving licence number"
      break
    case "officer":
      usernameLabel.textContent = "Username:"
      usernameInput.placeholder = "Enter your username"
      break
    case "admin":
      usernameLabel.textContent = "Username:"
      usernameInput.placeholder = "Enter admin username"
      break
  }
}

// Offence search functionality
function searchOffences(query, callback) {
  if (query.length < 2) {
    callback([])
    return
  }

  fetch("../api/search_offences.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ query: query }),
  })
    .then((response) => response.json())
    .then((data) => callback(data))
    .catch((error) => {
      console.error("Error:", error)
      callback([])
    })
}

// Setup offence search with suggestions
function setupOffenceSearch() {
  const descriptionInput = document.getElementById("offence_description")
  const suggestionsDiv = document.getElementById("offence_suggestions")

  if (!descriptionInput || !suggestionsDiv) return

  let selectedOffenceId = null
  let searchTimeout = null

  descriptionInput.addEventListener("input", function () {
    const query = this.value.trim()

    // Clear previous timeout
    if (searchTimeout) {
      clearTimeout(searchTimeout)
    }

    // Show loading state
    if (query.length >= 1) {
      suggestionsDiv.innerHTML = '<div class="search-loading">🔍 Searching offences...</div>'
      suggestionsDiv.style.display = "block"
    }

    // Debounce search
    searchTimeout = setTimeout(() => {
      searchOffences(query, (offences) => {
        suggestionsDiv.innerHTML = ""

        if (offences.length > 0) {
          suggestionsDiv.style.display = "block"

          // Add header if it's a search result
          if (query.length > 0) {
            const headerDiv = document.createElement("div")
            headerDiv.style.cssText =
              "padding: 8px 12px; background: #e3f2fd; border-bottom: 1px solid #ddd; font-weight: bold; font-size: 0.9em;"
            headerDiv.textContent = `Found ${offences.length} matching offence${offences.length > 1 ? "s" : ""}:`
            suggestionsDiv.appendChild(headerDiv)
          }

          offences.forEach((offence) => {
            const suggestionDiv = document.createElement("div")
            suggestionDiv.className = "offence-suggestion"
            suggestionDiv.innerHTML = `
                          <strong>${offence.description}</strong><br>
                          <small>Keyword: ${offence.keyword} | Fine: TZS ${Number.parseFloat(offence.amount_tzs).toLocaleString()}</small>
                      `

            suggestionDiv.addEventListener("click", () => {
              descriptionInput.value = offence.description
              selectedOffenceId = offence.id

              // Set the hidden field value
              const hiddenField = document.getElementById("selected_offence_id")
              if (hiddenField) {
                hiddenField.value = offence.id
                console.log("Set offence ID to:", offence.id)
                showOffenceSelected(offence)
              }

              suggestionsDiv.style.display = "none"

              // Visual feedback
              descriptionInput.style.borderColor = "#28a745"
              setTimeout(() => {
                descriptionInput.style.borderColor = ""
              }, 2000)
            })

            suggestionsDiv.appendChild(suggestionDiv)
          })
        } else if (query.length > 0) {
          suggestionsDiv.innerHTML =
            '<div class="search-no-results">❌ No matching offences found. Try keywords like "speed", "parking", "phone"</div>'
          suggestionsDiv.style.display = "block"
        } else {
          suggestionsDiv.style.display = "none"
        }
      })
    }, 300) // 300ms delay
  })

  // Show common offences on focus
  descriptionInput.addEventListener("focus", function () {
    if (this.value.length === 0) {
      searchOffences("", (offences) => {
        if (offences.length > 0) {
          suggestionsDiv.innerHTML = ""

          const headerDiv = document.createElement("div")
          headerDiv.style.cssText =
            "padding: 8px 12px; background: #e8f5e8; border-bottom: 1px solid #ddd; font-weight: bold; font-size: 0.9em;"
          headerDiv.textContent = "💡 Common Traffic Offences (click to select):"
          suggestionsDiv.appendChild(headerDiv)

          offences.forEach((offence) => {
            const suggestionDiv = document.createElement("div")
            suggestionDiv.className = "offence-suggestion"
            suggestionDiv.innerHTML = `
                          <strong>${offence.description}</strong><br>
                          <small>Keyword: ${offence.keyword} | Fine: TZS ${Number.parseFloat(offence.amount_tzs).toLocaleString()}</small>
                      `

            suggestionDiv.addEventListener("click", () => {
              this.value = offence.description
              selectedOffenceId = offence.id

              // Set the hidden field value
              const hiddenField = document.getElementById("selected_offence_id")
              if (hiddenField) {
                hiddenField.value = offence.id
                console.log("Set offence ID to:", offence.id)

                // Update debug info
                const debugOffenceId = document.getElementById("debug-offence-id")
                const debugFormData = document.getElementById("debug-form-data")
                if (debugOffenceId) debugOffenceId.textContent = offence.id
                if (debugFormData) debugFormData.textContent = `ID: ${offence.id}, Description: ${offence.description}`
              } else {
                console.error("Hidden field 'selected_offence_id' not found!")
              }

              suggestionsDiv.style.display = "none"

              // Visual feedback
              this.style.borderColor = "#28a745"
              setTimeout(() => {
                this.style.borderColor = ""
              }, 2000)
            })

            suggestionsDiv.appendChild(suggestionDiv)
          })

          suggestionsDiv.style.display = "block"
        }
      })
    }
  })

  // Hide suggestions when clicking outside
  document.addEventListener("click", (e) => {
    if (!descriptionInput.contains(e.target) && !suggestionsDiv.contains(e.target)) {
      suggestionsDiv.style.display = "none"
    }
  })

  // Clear selection when input is manually changed
  descriptionInput.addEventListener("input", () => {
    if (selectedOffenceId) {
      selectedOffenceId = null
      document.getElementById("selected_offence_id").value = ""
      hideOffenceSelected()
    }
  })
}

// Image preview functionality
function previewImage(input) {
  const preview = document.getElementById("image_preview")
  const file = input.files[0]

  if (file) {
    const reader = new FileReader()
    reader.onload = (e) => {
      preview.src = e.target.result
      preview.style.display = "block"
    }
    reader.readAsDataURL(file)
  } else {
    preview.style.display = "none"
  }
}

// Format currency
function formatCurrency(amount) {
  return (
    "TZS " +
    Number.parseFloat(amount).toLocaleString("en-US", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    })
  )
}

// Initialize page functionality
document.addEventListener("DOMContentLoaded", () => {
  // Setup offence search if on officer page
  setupOffenceSearch()

  // Format all currency displays
  const currencyElements = document.querySelectorAll(".currency")
  currencyElements.forEach((element) => {
    const amount = element.textContent
    element.textContent = formatCurrency(amount)
  })

  // Auto-hide alerts after 5 seconds
  const alerts = document.querySelectorAll(".alert")
  alerts.forEach((alert) => {
    setTimeout(() => {
      alert.style.opacity = "0"
      setTimeout(() => {
        alert.style.display = "none"
      }, 300)
    }, 5000)
  })

  // Add form submission validation
  const form = document.querySelector('form[method="POST"]')
  if (form) {
    form.addEventListener("submit", (e) => {
      const selectedOffenceId = document.getElementById("selected_offence_id").value
      const offenceDescription = document.getElementById("offence_description").value

      console.log("Form submission - Offence ID:", selectedOffenceId)
      console.log("Form submission - Description:", offenceDescription)

      if (!selectedOffenceId || selectedOffenceId === "") {
        e.preventDefault()
        alert("Please select an offence from the dropdown suggestions before submitting.")
        return false
      }
    })
  }
})

// Confirm delete actions
function confirmDelete(message) {
  return confirm(message || "Are you sure you want to delete this item?")
}

// Print functionality
function printPage() {
  window.print()
}

// Export to CSV (basic implementation)
function exportToCSV(tableId, filename) {
  const table = document.getElementById(tableId)
  if (!table) return

  const csv = []
  const rows = table.querySelectorAll("tr")

  rows.forEach((row) => {
    const cols = row.querySelectorAll("td, th")
    const rowData = []
    cols.forEach((col) => {
      rowData.push('"' + col.textContent.replace(/"/g, '""') + '"')
    })
    csv.push(rowData.join(","))
  })

  const csvContent = csv.join("\n")
  const blob = new Blob([csvContent], { type: "text/csv" })
  const url = window.URL.createObjectURL(blob)

  const a = document.createElement("a")
  a.href = url
  a.download = filename || "export.csv"
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  window.URL.revokeObjectURL(url)
}

// Camera functionality
let cameraStream = null
let capturedImageBlob = null

function openCamera() {
  const modal = document.getElementById("cameraModal")
  const video = document.getElementById("cameraVideo")

  modal.style.display = "block"

  // Request camera access
  navigator.mediaDevices
    .getUserMedia({
      video: {
        width: 400,
        height: 300,
        facingMode: "environment", // Use back camera on mobile
      },
    })
    .then((stream) => {
      cameraStream = stream
      video.srcObject = stream
    })
    .catch((err) => {
      console.error("Error accessing camera:", err)
      alert("Unable to access camera. Please check permissions or use file upload instead.")
      closeCamera()
    })
}

function closeCamera() {
  const modal = document.getElementById("cameraModal")
  const video = document.getElementById("cameraVideo")

  if (cameraStream) {
    cameraStream.getTracks().forEach((track) => track.stop())
    cameraStream = null
  }

  video.srcObject = null
  modal.style.display = "none"
}

function capturePhoto() {
  const video = document.getElementById("cameraVideo")
  const canvas = document.getElementById("cameraCanvas")
  const preview = document.getElementById("image_preview")
  const fileInput = document.getElementById("incident_image")

  const context = canvas.getContext("2d")
  context.drawImage(video, 0, 0, 400, 300)

  // Convert canvas to blob
  canvas.toBlob(
    (blob) => {
      capturedImageBlob = blob

      // Show preview
      const url = URL.createObjectURL(blob)
      preview.src = url
      preview.style.display = "block"

      // Create a new File object and assign to input
      const file = new File([blob], "captured_photo.jpg", { type: "image/jpeg" })
      const dataTransfer = new DataTransfer()
      dataTransfer.items.add(file)
      fileInput.files = dataTransfer.files

      closeCamera()
    },
    "image/jpeg",
    0.8,
  )
}

// Close camera modal when clicking outside
document.addEventListener("click", (e) => {
  const modal = document.getElementById("cameraModal")
  if (modal && e.target === modal) {
    closeCamera()
  }
})

function showOffenceSelected(offence) {
  const selectedInfo = document.getElementById("selected-offence-info")
  if (selectedInfo) {
    selectedInfo.style.display = "block"
    selectedInfo.innerHTML = `✅ Selected: ${offence.description} (TZS ${Number.parseFloat(offence.amount_tzs).toLocaleString()})`
  }
}

function hideOffenceSelected() {
  const selectedInfo = document.getElementById("selected-offence-info")
  if (selectedInfo) {
    selectedInfo.style.display = "none"
  }
}

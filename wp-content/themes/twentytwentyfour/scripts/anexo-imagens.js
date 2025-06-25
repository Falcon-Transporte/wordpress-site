const fileInput = document.getElementById("imagem_ocorrido");
const uploadArea = document.getElementById("upload-area");
const previewContainer = document.getElementById("preview-container");
let selectedFiles = [];

// Ao clicar na área de upload, abre o seletor de arquivos
uploadArea.addEventListener("click", () => fileInput.click());

// Permitir arrastar e soltar arquivos na área de upload
uploadArea.addEventListener("dragover", (event) => {
  event.preventDefault();
  uploadArea.style.background = "#e9ecef";
});

uploadArea.addEventListener("dragleave", () => {
  uploadArea.style.background = "";
});

uploadArea.addEventListener("drop", (event) => {
  event.preventDefault();
  uploadArea.style.background = "";
  handleFiles(event.dataTransfer.files);
});

fileInput.addEventListener("change", (event) =>
  handleFiles(event.target.files)
);

function handleFiles(files) {
  if (selectedFiles.length + files.length > 3) {
    alert("Você pode adicionar no máximo 3 imagens.");
    return;
  }

  const allowedTypes = ["image/jpeg", "image/png", "image/gif"];

  for (let file of files) {
    if (selectedFiles.length >= 3) break;

    if (file.size > 2 * 1024 * 1024) {
      alert(`"${file.name}" excede o limite de 2MB.`);
      continue;
    }

    if (!allowedTypes.includes(file.type)) {
      alert(`"${file.name}" não é um formato permitido.`);
      continue;
    }

    selectedFiles.push(file);
    displayPreview(file);
  }
}

function displayPreview(file) {
  const reader = new FileReader();
  reader.onload = function (e) {
    const previewBox = document.createElement("div");
    previewBox.classList.add("preview-box");

    const img = document.createElement("img");
    img.src = e.target.result;

    const removeBtn = document.createElement("button");
    removeBtn.classList.add("remove-btn");
    removeBtn.innerHTML = "✖";
    removeBtn.onclick = () => removeImage(file, previewBox);

    previewBox.appendChild(img);
    previewBox.appendChild(removeBtn);
    previewContainer.appendChild(previewBox);
  };
  reader.readAsDataURL(file);
}

function removeImage(file, previewBox) {
  selectedFiles = selectedFiles.filter((f) => f !== file);
  previewBox.remove();
}

// ⬇️ ESSA PARTE AQUI É O ENVIO AJAX QUE GARANTE QUE selectedFiles SEJA ENVIADO DE VERDADE
const form = document.getElementById("form-relatorio");

form.addEventListener("submit", function (e) {
  e.preventDefault();

  const formData = new FormData(form);

  selectedFiles.forEach((file) => {
    formData.append("imagem_ocorrido[]", file);
  });

  fetch(form.action || window.location.href, {
    method: "POST",
    body: formData,
  })
    .then((res) => {
      if (res.redirected) {
        window.location.href = res.url;
      } else {
        return res.text();
      }
    })
    .then((data) => {
      if (typeof data === "string") console.log(data); // apenas se não redirecionar
    })
    .catch((err) => {
      console.error("Erro no envio:", err);
      alert("Erro ao enviar. Verifique sua conexão e tente novamente.");
    });
});

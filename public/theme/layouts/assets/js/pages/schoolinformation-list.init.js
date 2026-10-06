// // Global variables
// var perPage = 5,
//     editlist = false,
//     checkAll = document.getElementById("checkAll"),
//     options = {
//         valueNames: [
//             "id",
//             "name",
//             "email",
//             "status",
//             "no_of_times_school_opened",
//             "date_school_opened",
//             "date_next_term_begins",
//             "created_at"
//         ],
//     },
//     schoolList = new List("schoolList", options);

// // Cropper instances
// let schoolLogoCropper = null;
// let appLogoCropper = null;
// let editSchoolLogoCropper = null;
// let editAppLogoCropper = null;

// // Store cropped images
// let croppedSchoolLogoBlob = null;
// let croppedAppLogoBlob = null;
// let croppedEditSchoolLogoBlob = null;
// let croppedEditAppLogoBlob = null;

// // Form field variables
// var addIdField = document.getElementById("add-id-field"),
//     addNameField = document.getElementById("school_name"),
//     addAddressField = document.getElementById("school_address"),
//     addPhoneField = document.getElementById("school_phone"),
//     addEmailField = document.getElementById("school_email"),
//     addLogoField = document.getElementById("school_logo"),
//     addMottoField = document.getElementById("school_motto"),
//     addWebsiteField = document.getElementById("school_website"),
//     addTimesOpenedField = document.getElementById("no_of_times_school_opened"),
//     addDateOpenedField = document.getElementById("date_school_opened"),
//     addNextTermField = document.getElementById("date_next_term_begins"),
//     addStatusField = document.getElementById("is_active"),
//     editIdField = document.getElementById("edit-id-field"),
//     editNameField = document.getElementById("edit_school_name"),
//     editAddressField = document.getElementById("edit_school_address"),
//     editPhoneField = document.getElementById("edit_school_phone"),
//     editEmailField = document.getElementById("edit_school_email"),
//     editLogoField = document.getElementById("edit_school_logo"),
//     editMottoField = document.getElementById("edit_school_motto"),
//     editWebsiteField = document.getElementById("edit_school_website"),
//     editTimesOpenedField = document.getElementById("edit_no_of_times_school_opened"),
//     editDateOpenedField = document.getElementById("edit_date_school_opened"),
//     editNextTermField = document.getElementById("edit_date_next_term_begins"),
//     editStatusField = document.getElementById("edit_is_active");

// // Status chart
// let statusChart = null;

// // Initialize everything when DOM is loaded
// document.addEventListener("DOMContentLoaded", function () {
//     console.log("DOM loaded, initializing school information...");

//     // Initialize chart
//     initStatusChart();

//     // Initialize List.js
//     initListJS();

//     // Initialize croppers
//     initAddModalCroppers();

//     // Initialize event listeners
//     initEventListeners();

//     // Initialize choices if available
//     initChoices();
// });

// // Initialize status chart
// function initStatusChart() {
//     const ctx = document.getElementById("schoolsByStatusChart");
//     if (!ctx) return;

//     try {
//         const statusData = JSON.parse(ctx.getAttribute('data-status') || '{"Active":0,"Inactive":0}');

//         statusChart = new Chart(ctx.getContext("2d"), {
//             type: "bar",
//             data: {
//                 labels: Object.keys(statusData),
//                 datasets: [{
//                     label: "Schools by Status",
//                     data: Object.values(statusData),
//                     backgroundColor: ["#28a745", "#6c757d"],
//                     borderColor: ["#28a745", "#6c757d"],
//                     borderWidth: 1
//                 }]
//             },
//             options: {
//                 scales: {
//                     y: {
//                         beginAtZero: true,
//                         title: {
//                             display: true,
//                             text: "Number of Schools"
//                         }
//                     },
//                     x: {
//                         title: {
//                             display: true,
//                             text: "Status"
//                         }
//                     }
//                 },
//                 plugins: {
//                     legend: {
//                         display: true,
//                         position: "top"
//                     }
//                 }
//             }
//         });
//     } catch (error) {
//         console.error("Error initializing chart:", error);
//     }
// }

// // Initialize List.js
// function initListJS() {
//     console.log("Initial schoolList items:", schoolList.items.length);

//     schoolList.on("updated", function (e) {
//         console.log("List.js updated, matching items:", e.matchingItems.length, "total items:", schoolList.items.length);
//         const noResultElements = document.getElementsByClassName("noresult");
//         if (noResultElements.length > 0) {
//             noResultElements[0].style.display = e.matchingItems.length === 0 ? "block" : "none";
//         }
//         setTimeout(() => {
//             refreshCallbacks();
//             ischeckboxcheck();
//         }, 100);
//     });

//     refreshCallbacks();
//     ischeckboxcheck();
// }

// // Initialize choices dropdowns
// function initChoices() {
//     if (typeof Choices !== 'undefined') {
//         try {
//             var statusFilterVal = new Choices(document.getElementById("idStatus"), { searchEnabled: true });
//             var emailFilterVal = new Choices(document.getElementById("idEmail"), { searchEnabled: true });
//         } catch (error) {
//             console.warn("Choices.js initialization error:", error);
//         }
//     } else {
//         console.warn("Choices.js not available, falling back to native select");
//     }
// }

// // Initialize add modal croppers
// function initAddModalCroppers() {
//     // School Logo Cropper
//     const schoolLogoInput = document.getElementById('school_logo');
//     const schoolLogoCropperContainer = document.getElementById('school-logo-cropper-container');
//     const schoolLogoCropperImage = document.getElementById('school-logo-cropper');
//     const schoolCropBtn = document.getElementById('school-crop-btn');
//     const schoolResetCropBtn = document.getElementById('school-reset-crop-btn');
//     const schoolCropWidth = document.getElementById('school-crop-width');
//     const schoolCropHeight = document.getElementById('school-crop-height');
//     const schoolLogoPreview = document.getElementById('school-logo-preview');

//     if (schoolLogoInput && schoolLogoCropperContainer) {
//         schoolLogoInput.addEventListener('change', function(e) {
//             handleImageUpload(e, {
//                 cropperContainer: schoolLogoCropperContainer,
//                 cropperImage: schoolLogoCropperImage,
//                 cropperInstance: schoolLogoCropper,
//                 cropWidth: schoolCropWidth,
//                 cropHeight: schoolCropHeight,
//                 previewElement: schoolLogoPreview,
//                 inputElement: schoolLogoInput,
//                 type: 'school',
//                 blobStorage: 'croppedSchoolLogoBlob'
//             });
//         });

//         // Crop button
//         if (schoolCropBtn) {
//             schoolCropBtn.addEventListener('click', function() {
//                 handleCropImage({
//                     cropperInstance: schoolLogoCropper,
//                     cropWidth: schoolCropWidth,
//                     cropHeight: schoolCropHeight,
//                     previewElement: schoolLogoPreview,
//                     type: 'school',
//                     blobStorage: 'croppedSchoolLogoBlob'
//                 });
//             });
//         }

//         // Reset button
//         if (schoolResetCropBtn) {
//             schoolResetCropBtn.addEventListener('click', function() {
//                 handleResetCropper({
//                     cropperInstance: schoolLogoCropper,
//                     previewElement: schoolLogoPreview,
//                     cropperContainer: schoolLogoCropperContainer,
//                     blobStorage: 'croppedSchoolLogoBlob',
//                     instanceName: 'schoolLogoCropper'
//                 });
//             });
//         }

//         // Update aspect ratio
//         if (schoolCropWidth && schoolCropHeight) {
//             schoolCropWidth.addEventListener('change', () => updateAspectRatio(schoolLogoCropper, schoolCropWidth, schoolCropHeight));
//             schoolCropHeight.addEventListener('change', () => updateAspectRatio(schoolLogoCropper, schoolCropWidth, schoolCropHeight));
//         }
//     }

//     // App Logo Cropper
//     const appLogoInput = document.getElementById('app_logo');
//     const appLogoCropperContainer = document.getElementById('app-logo-cropper-container');
//     const appLogoCropperImage = document.getElementById('app-logo-cropper');
//     const appCropBtn = document.getElementById('app-crop-btn');
//     const appResetCropBtn = document.getElementById('app-reset-crop-btn');
//     const appCropWidth = document.getElementById('app-crop-width');
//     const appCropHeight = document.getElementById('app-crop-height');
//     const appLogoPreview = document.getElementById('app-logo-preview');

//     if (appLogoInput && appLogoCropperContainer) {
//         appLogoInput.addEventListener('change', function(e) {
//             handleImageUpload(e, {
//                 cropperContainer: appLogoCropperContainer,
//                 cropperImage: appLogoCropperImage,
//                 cropperInstance: appLogoCropper,
//                 cropWidth: appCropWidth,
//                 cropHeight: appCropHeight,
//                 previewElement: appLogoPreview,
//                 inputElement: appLogoInput,
//                 type: 'app',
//                 blobStorage: 'croppedAppLogoBlob'
//             });
//         });

//         // Crop button
//         if (appCropBtn) {
//             appCropBtn.addEventListener('click', function() {
//                 handleCropImage({
//                     cropperInstance: appLogoCropper,
//                     cropWidth: appCropWidth,
//                     cropHeight: appCropHeight,
//                     previewElement: appLogoPreview,
//                     type: 'app',
//                     blobStorage: 'croppedAppLogoBlob'
//                 });
//             });
//         }

//         // Reset button
//         if (appResetCropBtn) {
//             appResetCropBtn.addEventListener('click', function() {
//                 handleResetCropper({
//                     cropperInstance: appLogoCropper,
//                     previewElement: appLogoPreview,
//                     cropperContainer: appLogoCropperContainer,
//                     blobStorage: 'croppedAppLogoBlob',
//                     instanceName: 'appLogoCropper'
//                 });
//             });
//         }

//         // Update aspect ratio
//         if (appCropWidth && appCropHeight) {
//             appCropWidth.addEventListener('change', () => updateAspectRatio(appLogoCropper, appCropWidth, appCropHeight));
//             appCropHeight.addEventListener('change', () => updateAspectRatio(appLogoCropper, appCropWidth, appCropHeight));
//         }
//     }
// }

// // Initialize edit modal croppers when modal is shown
// function initEditModalCroppers() {
//     // Edit School Logo Cropper
//     const editSchoolLogoInput = document.getElementById('edit_school_logo');
//     const editSchoolLogoCropperContainer = document.getElementById('edit-school-logo-cropper-container');
//     const editSchoolLogoCropperImage = document.getElementById('edit-school-logo-cropper');
//     const editSchoolCropBtn = document.getElementById('edit-school-crop-btn');
//     const editSchoolResetCropBtn = document.getElementById('edit-school-reset-crop-btn');
//     const editSchoolCropWidth = document.getElementById('edit-school-crop-width');
//     const editSchoolCropHeight = document.getElementById('edit-school-crop-height');
//     const editSchoolLogoPreview = document.getElementById('edit-school-logo-preview');

//     if (editSchoolLogoInput && editSchoolLogoCropperContainer) {
//         // Remove existing event listener to prevent duplicates
//         editSchoolLogoInput.addEventListener('change', function(e) {
//             handleImageUpload(e, {
//                 cropperContainer: editSchoolLogoCropperContainer,
//                 cropperImage: editSchoolLogoCropperImage,
//                 cropperInstance: editSchoolLogoCropper,
//                 cropWidth: editSchoolCropWidth,
//                 cropHeight: editSchoolCropHeight,
//                 previewElement: editSchoolLogoPreview,
//                 inputElement: editSchoolLogoInput,
//                 type: 'school',
//                 blobStorage: 'croppedEditSchoolLogoBlob'
//             });
//         });

//         // Crop button
//         if (editSchoolCropBtn) {
//             editSchoolCropBtn.addEventListener('click', function() {
//                 handleCropImage({
//                     cropperInstance: editSchoolLogoCropper,
//                     cropWidth: editSchoolCropWidth,
//                     cropHeight: editSchoolCropHeight,
//                     previewElement: editSchoolLogoPreview,
//                     type: 'school',
//                     blobStorage: 'croppedEditSchoolLogoBlob'
//                 });
//             });
//         }

//         // Reset button
//         if (editSchoolResetCropBtn) {
//             editSchoolResetCropBtn.addEventListener('click', function() {
//                 handleResetCropper({
//                     cropperInstance: editSchoolLogoCropper,
//                     previewElement: editSchoolLogoPreview,
//                     cropperContainer: editSchoolLogoCropperContainer,
//                     blobStorage: 'croppedEditSchoolLogoBlob',
//                     instanceName: 'editSchoolLogoCropper'
//                 });
//             });
//         }

//         // Update aspect ratio
//         if (editSchoolCropWidth && editSchoolCropHeight) {
//             editSchoolCropWidth.addEventListener('change', () => updateAspectRatio(editSchoolLogoCropper, editSchoolCropWidth, editSchoolCropHeight));
//             editSchoolCropHeight.addEventListener('change', () => updateAspectRatio(editSchoolLogoCropper, editSchoolCropWidth, editSchoolCropHeight));
//         }
//     }

//     // Edit App Logo Cropper
//     const editAppLogoInput = document.getElementById('edit_app_logo');
//     const editAppLogoCropperContainer = document.getElementById('edit-app-logo-cropper-container');
//     const editAppLogoCropperImage = document.getElementById('edit-app-logo-cropper');
//     const editAppCropBtn = document.getElementById('edit-app-crop-btn');
//     const editAppResetCropBtn = document.getElementById('edit-app-reset-crop-btn');
//     const editAppCropWidth = document.getElementById('edit-app-crop-width');
//     const editAppCropHeight = document.getElementById('edit-app-crop-height');
//     const editAppLogoPreview = document.getElementById('edit-app-logo-preview');

//     if (editAppLogoInput && editAppLogoCropperContainer) {
//         // Remove existing event listener to prevent duplicates
//         editAppLogoInput.addEventListener('change', function(e) {
//             handleImageUpload(e, {
//                 cropperContainer: editAppLogoCropperContainer,
//                 cropperImage: editAppLogoCropperImage,
//                 cropperInstance: editAppLogoCropper,
//                 cropWidth: editAppCropWidth,
//                 cropHeight: editAppCropHeight,
//                 previewElement: editAppLogoPreview,
//                 inputElement: editAppLogoInput,
//                 type: 'app',
//                 blobStorage: 'croppedEditAppLogoBlob'
//             });
//         });

//         // Crop button
//         if (editAppCropBtn) {
//             editAppCropBtn.addEventListener('click', function() {
//                 handleCropImage({
//                     cropperInstance: editAppLogoCropper,
//                     cropWidth: editAppCropWidth,
//                     cropHeight: editAppCropHeight,
//                     previewElement: editAppLogoPreview,
//                     type: 'app',
//                     blobStorage: 'croppedEditAppLogoBlob'
//                 });
//             });
//         }

//         // Reset button
//         if (editAppResetCropBtn) {
//             editAppResetCropBtn.addEventListener('click', function() {
//                 handleResetCropper({
//                     cropperInstance: editAppLogoCropper,
//                     previewElement: editAppLogoPreview,
//                     cropperContainer: editAppLogoCropperContainer,
//                     blobStorage: 'croppedEditAppLogoBlob',
//                     instanceName: 'editAppLogoCropper'
//                 });
//             });
//         }

//         // Update aspect ratio
//         if (editAppCropWidth && editAppCropHeight) {
//             editAppCropWidth.addEventListener('change', () => updateAspectRatio(editAppLogoCropper, editAppCropWidth, editAppCropHeight));
//             editAppCropHeight.addEventListener('change', () => updateAspectRatio(editAppLogoCropper, editAppCropWidth, editAppCropHeight));
//         }
//     }
// }

// // Handle image upload for cropper
// function handleImageUpload(e, options) {
//     const file = e.target.files[0];
//     if (!file) return;

//     // Check file type
//     if (!file.type.match('image.*')) {
//         showAlert('error', 'Invalid File', 'Please select an image file (JPEG, PNG, JPG, WebP)');
//         return;
//     }

//     // Check file size (5MB limit)
//     if (file.size > 5 * 1024 * 1024) {
//         showAlert('error', 'File Too Large', 'Image must be less than 5MB');
//         return;
//     }

//     const reader = new FileReader();
//     reader.onload = function(e) {
//         options.cropperContainer.classList.remove('d-none');
//         options.cropperImage.src = e.target.result;

//         // Determine which cropper instance to initialize
//         let cropperToDestroy = null;
//         if (options.blobStorage === 'croppedSchoolLogoBlob') {
//             cropperToDestroy = schoolLogoCropper;
//         } else if (options.blobStorage === 'croppedAppLogoBlob') {
//             cropperToDestroy = appLogoCropper;
//         } else if (options.blobStorage === 'croppedEditSchoolLogoBlob') {
//             cropperToDestroy = editSchoolLogoCropper;
//         } else if (options.blobStorage === 'croppedEditAppLogoBlob') {
//             cropperToDestroy = editAppLogoCropper;
//         }

//         // Destroy existing cropper
//         if (cropperToDestroy) {
//             cropperToDestroy.destroy();
//         }

//         // Create new cropper and assign to the correct variable
//         const newCropper = new Cropper(options.cropperImage, {
//             aspectRatio: options.cropWidth.value / options.cropHeight.value,
//             viewMode: 1,
//             autoCropArea: 1,
//             responsive: true,
//             guides: true,
//             center: true,
//             cropBoxMovable: true,
//             cropBoxResizable: true
//         });

//         // Assign to correct global variable
//         if (options.blobStorage === 'croppedSchoolLogoBlob') {
//             schoolLogoCropper = newCropper;
//         } else if (options.blobStorage === 'croppedAppLogoBlob') {
//             appLogoCropper = newCropper;
//         } else if (options.blobStorage === 'croppedEditSchoolLogoBlob') {
//             editSchoolLogoCropper = newCropper;
//         } else if (options.blobStorage === 'croppedEditAppLogoBlob') {
//             editAppLogoCropper = newCropper;
//         }

//         console.log(`${options.type} logo cropper initialized for ${options.blobStorage}`);
//     };
//     reader.readAsDataURL(file);
// }

// // Handle crop image
// function handleCropImage(options) {
//     let cropper;

//     // Determine which cropper to use based on the blob storage identifier
//     if (options.blobStorage === 'croppedSchoolLogoBlob') {
//         cropper = schoolLogoCropper;
//     } else if (options.blobStorage === 'croppedAppLogoBlob') {
//         cropper = appLogoCropper;
//     } else if (options.blobStorage === 'croppedEditSchoolLogoBlob') {
//         cropper = editSchoolLogoCropper;
//     } else if (options.blobStorage === 'croppedEditAppLogoBlob') {
//         cropper = editAppLogoCropper;
//     }

//     if (!cropper) {
//         showAlert('warning', 'No Image', 'Please select an image first');
//         return;
//     }

//     const w = parseInt(options.cropWidth.value) || (options.type === 'school' ? 300 : 200);
//     const h = parseInt(options.cropHeight.value) || (options.type === 'school' ? 300 : 200);

//     const canvas = cropper.getCroppedCanvas({
//         width: w,
//         height: h,
//         imageSmoothingEnabled: true,
//         imageSmoothingQuality: 'high'
//     });

//     canvas.toBlob(function(blob) {
//         // Store the blob in the appropriate variable
//         if (options.blobStorage === 'croppedSchoolLogoBlob') {
//             croppedSchoolLogoBlob = blob;
//         } else if (options.blobStorage === 'croppedAppLogoBlob') {
//             croppedAppLogoBlob = blob;
//         } else if (options.blobStorage === 'croppedEditSchoolLogoBlob') {
//             croppedEditSchoolLogoBlob = blob;
//         } else if (options.blobStorage === 'croppedEditAppLogoBlob') {
//             croppedEditAppLogoBlob = blob;
//         }

//         // Show preview
//         options.previewElement.innerHTML = `
//             <div class="alert alert-success p-2 mb-2">
//                 <i class="ri-check-line me-1"></i> ${options.type.charAt(0).toUpperCase() + options.type.slice(1)} logo cropped successfully (${w}x${h}px)
//             </div>
//             <img src="${canvas.toDataURL()}" class="img-thumbnail mt-2" style="max-width: 150px; max-height: 150px;">
//         `;

//         console.log(`${options.type} logo cropped and stored in ${options.blobStorage}`);
//     }, 'image/png');
// }

// // Handle reset cropper
// function handleResetCropper(options) {
//     // Get the actual cropper instance
//     let cropperToDestroy = null;
//     if (options.instanceName === 'schoolLogoCropper') {
//         cropperToDestroy = schoolLogoCropper;
//         schoolLogoCropper = null;
//     } else if (options.instanceName === 'appLogoCropper') {
//         cropperToDestroy = appLogoCropper;
//         appLogoCropper = null;
//     } else if (options.instanceName === 'editSchoolLogoCropper') {
//         cropperToDestroy = editSchoolLogoCropper;
//         editSchoolLogoCropper = null;
//     } else if (options.instanceName === 'editAppLogoCropper') {
//         cropperToDestroy = editAppLogoCropper;
//         editAppLogoCropper = null;
//     }

//     if (cropperToDestroy) {
//         cropperToDestroy.destroy();
//     }

//     // Clear the stored blob
//     if (options.blobStorage === 'croppedSchoolLogoBlob') {
//         croppedSchoolLogoBlob = null;
//     } else if (options.blobStorage === 'croppedAppLogoBlob') {
//         croppedAppLogoBlob = null;
//     } else if (options.blobStorage === 'croppedEditSchoolLogoBlob') {
//         croppedEditSchoolLogoBlob = null;
//     } else if (options.blobStorage === 'croppedEditAppLogoBlob') {
//         croppedEditAppLogoBlob = null;
//     }

//     if (options.previewElement) {
//         options.previewElement.innerHTML = '';
//     }

//     if (options.cropperContainer) {
//         options.cropperContainer.classList.add('d-none');
//     }
// }

// // Update aspect ratio for cropper
// function updateAspectRatio(cropper, widthInput, heightInput) {
//     if (cropper) {
//         const w = parseInt(widthInput.value) || 200;
//         const h = parseInt(heightInput.value) || 200;
//         if (w > 0 && h > 0) {
//             cropper.setAspectRatio(w / h);
//         }
//     }
// }

// // Initialize all event listeners
// function initEventListeners() {
//     // Check all checkbox
//     if (checkAll) {
//         checkAll.onclick = function () {
//             console.log("checkAll clicked");
//             var checkboxes = document.querySelectorAll('tbody input[name="chk_child"]');
//             console.log("Checkboxes found:", checkboxes.length);
//             checkboxes.forEach((checkbox) => {
//                 checkbox.checked = this.checked;
//                 const row = checkbox.closest("tr");
//                 if (checkbox.checked) {
//                     row.classList.add("table-active");
//                 } else {
//                     row.classList.remove("table-active");
//                 }
//             });
//             const checkedCount = document.querySelectorAll('tbody input[name="chk_child"]:checked').length;
//             const removeActions = document.getElementById("remove-actions");
//             if (removeActions) {
//                 removeActions.classList.toggle("d-none", checkedCount === 0);
//             }
//         };
//     }

//     // Add school form submission
//     const addSchoolForm = document.getElementById("add-school-form");
//     if (addSchoolForm) {
//         addSchoolForm.addEventListener("submit", handleAddSchoolSubmit);
//     }

//     // Edit school form submission
//     const editSchoolForm = document.getElementById("edit-school-form");
//     if (editSchoolForm) {
//         editSchoolForm.addEventListener("submit", handleEditSchoolSubmit);
//     }

//     // Delete record
//     const deleteRecordBtn = document.getElementById("delete-record");
//     if (deleteRecordBtn) {
//         deleteRecordBtn.addEventListener("click", handleDeleteRecord);
//     }

//     // Modal cleanup
//     const showModal = document.getElementById("showModal");
//     const editModal = document.getElementById("editModal");

//     if (showModal) {
//         showModal.addEventListener('hidden.bs.modal', function() {
//             handleModalClose('add');
//         });
//     }

//     if (editModal) {
//         editModal.addEventListener('hidden.bs.modal', function() {
//             handleModalClose('edit');
//         });

//         // Initialize croppers when edit modal is shown
//         editModal.addEventListener('show.bs.modal', function() {
//             // Initialize edit modal croppers
//             setTimeout(() => {
//                 initEditModalCroppers();
//             }, 300);
//         });
//     }
// }

// // Checkbox handling
// function ischeckboxcheck() {
//     const checkboxes = document.querySelectorAll('tbody input[name="chk_child"]');
//     checkboxes.forEach((checkbox) => {
//         checkbox.removeEventListener("change", handleCheckboxChange);
//         checkbox.addEventListener("change", handleCheckboxChange);
//     });
// }

// function handleCheckboxChange(e) {
//     const row = e.target.closest("tr");
//     if (e.target.checked) {
//         row.classList.add("table-active");
//     } else {
//         row.classList.remove("table-active");
//     }
//     const checkedCount = document.querySelectorAll('tbody input[name="chk_child"]:checked').length;
//     const removeActions = document.getElementById("remove-actions");
//     if (removeActions) {
//         removeActions.classList.toggle("d-none", checkedCount === 0);
//     }
//     const allCheckboxes = document.querySelectorAll('tbody input[name="chk_child"]');
//     if (checkAll) {
//         checkAll.checked = allCheckboxes.length > 0 && allCheckboxes.length === checkedCount;
//     }
// }

// // Refresh callbacks for edit/remove buttons
// function refreshCallbacks() {
//     console.log("refreshCallbacks executed at", new Date().toISOString());
//     var removeButtons = document.getElementsByClassName("remove-item-btn");
//     var editButtons = document.getElementsByClassName("edit-item-btn");
//     console.log("Attaching event listeners to", removeButtons.length, "remove buttons and", editButtons.length, "edit buttons");

//     Array.from(removeButtons).forEach(function (btn) {
//         btn.removeEventListener("click", handleRemoveClick);
//         btn.addEventListener("click", handleRemoveClick);
//     });

//     Array.from(editButtons).forEach(function (btn) {
//         btn.removeEventListener("click", handleEditClick);
//         btn.addEventListener("click", handleEditClick);
//     });
// }

// // Remove button click handler
// function handleRemoveClick(e) {
//     e.preventDefault();
//     try {
//         var itemId = e.target.closest("tr").querySelector(".id").getAttribute("data-id");
//         console.log("Remove button clicked for ID:", itemId);
//         // Store ID in modal for deletion
//         const modal = document.getElementById("deleteRecordModal");
//         if (modal) {
//             modal.dataset.itemId = itemId;
//             var bsModal = new bootstrap.Modal(modal);
//             bsModal.show();
//         }
//     } catch (error) {
//         console.error("Error in remove-item-btn click:", error);
//         showAlert('error', 'Error', 'Failed to initiate delete');
//     }
// }

// // Edit button click handler

// // Edit button click handler
// function handleEditClick(e) {
//     e.preventDefault();
//     try {
//         var itemId = e.target.closest("tr").querySelector(".id").getAttribute("data-id");
//         console.log("Edit button clicked for ID:", itemId);

//         // Show loading state
//         showLoading(true, 'edit');

//         // First try the edit-json endpoint
//         fetch(`/school-info/${itemId}/edit-json`, {
//             headers: {
//                 'Accept': 'application/json',
//                 'X-Requested-With': 'XMLHttpRequest',
//                 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
//             }
//         })
//         .then(response => {
//             // Check if response is JSON
//             const contentType = response.headers.get('content-type');

//             if (!response.ok) {
//                 // If not 200 OK, throw error
//                 throw new Error(`HTTP ${response.status}: ${response.statusText}`);
//             }

//             if (!contentType || !contentType.includes('application/json')) {
//                 console.warn('Response is not JSON:', contentType);
//                 // If not JSON, try alternative method
//                 return null;
//             }

//             return response.json();
//         })
//         .then(data => {
//             if (data === null) {
//                 // JSON endpoint failed, use fallback method
//                 console.log('JSON endpoint failed, using fallback method');
//                 return fetchSchoolDataFallback(itemId);
//             }

//             if (!data.success) {
//                 throw new Error(data.message || 'Failed to load data');
//             }

//             // Populate edit form
//             populateEditForm(data.school);

//             // Show modal
//             var modal = new bootstrap.Modal(document.getElementById("editModal"));
//             modal.show();
//         })
//         .catch(error => {
//             console.error('Error loading school data via JSON:', error);

//             // Try fallback method
//             console.log('Trying fallback method...');
//             fetchSchoolDataFallback(itemId)
//                 .then(data => {
//                     populateEditForm(data);
//                     var modal = new bootstrap.Modal(document.getElementById("editModal"));
//                     modal.show();
//                 })
//                 .catch(fallbackError => {
//                     console.error('Fallback also failed:', fallbackError);
//                     showAlert('error', 'Error', 'Failed to load school data. Please try again or contact support.');
//                 });
//         })
//         .finally(() => {
//             showLoading(false, 'edit');
//         });

//     } catch (error) {
//         console.error("Error in edit-item-btn click:", error);
//         showAlert('error', 'Error', 'Failed to open edit modal');
//         showLoading(false, 'edit');
//     }
// }

// // Fallback method to get data from table row
// function fetchSchoolDataFallback(itemId) {
//     return new Promise((resolve, reject) => {
//         try {
//             // Find the table row with this ID
//             const rows = document.querySelectorAll('tbody tr');
//             let schoolData = null;

//             rows.forEach(row => {
//                 const rowId = row.querySelector('.id')?.getAttribute('data-id');
//                 if (rowId === itemId) {
//                     schoolData = {
//                         id: itemId,
//                         school_name: row.querySelector('.name a')?.innerText || '',
//                         school_address: row.querySelector('.name')?.getAttribute('data-address') || '',
//                         school_email: row.querySelector('.email')?.innerText || '',
//                         school_phone: '', // Not available in table
//                         school_motto: row.querySelector('.name')?.getAttribute('data-motto') || '',
//                         school_website: row.querySelector('.name')?.getAttribute('data-website') || '',
//                         no_of_times_school_opened: row.querySelector('.no_of_times_school_opened')?.getAttribute('data-no_of_times_school_opened') || '',
//                         date_school_opened: row.querySelector('.date_school_opened')?.getAttribute('data-date_school_opened') || '',
//                         date_next_term_begins: row.querySelector('.date_next_term_begins')?.getAttribute('data-date_next_term_begins') || '',
//                         is_active: row.querySelector('.status')?.getAttribute('data-status') === 'Active',
//                         logo_url: null,
//                         app_logo_url: null
//                     };
//                 }
//             });

//             if (schoolData) {
//                 resolve(schoolData);
//             } else {
//                 reject(new Error('School data not found in table'));
//             }
//         } catch (error) {
//             reject(error);
//         }
//     });
// }
// // Populate edit form with data
// function populateEditForm(school) {
//     if (editIdField) editIdField.value = school.id || '';
//     if (editNameField) editNameField.value = school.school_name || '';
//     if (editAddressField) editAddressField.value = school.school_address || '';
//     if (editPhoneField) editPhoneField.value = school.school_phone || '';
//     if (editEmailField) editEmailField.value = school.school_email || '';
//     if (editMottoField) editMottoField.value = school.school_motto || '';
//     if (editWebsiteField) editWebsiteField.value = school.school_website || '';
//     if (editTimesOpenedField) editTimesOpenedField.value = school.no_of_times_school_opened || '';
//     if (editDateOpenedField) editDateOpenedField.value = school.date_school_opened || '';
//     if (editNextTermField) editNextTermField.value = school.date_next_term_begins || '';
//     if (editStatusField) editStatusField.checked = school.is_active || false;

//     // Logo previews
//     const schoolLogoPrev = document.getElementById('edit-school-logo-preview');
//     if (schoolLogoPrev) {
//         schoolLogoPrev.innerHTML = school.logo_url
//             ? `<p class="text-muted mb-1">Current Logo:</p><img src="${school.logo_url}" class="img-thumbnail" style="max-width:150px;">`
//             : '<p class="text-muted">No logo uploaded</p>';
//     }

//     const appLogoPrev = document.getElementById('edit-app-logo-preview');
//     if (appLogoPrev) {
//         appLogoPrev.innerHTML = school.app_logo_url
//             ? `<p class="text-muted mb-1">Current App Logo:</p><img src="${school.app_logo_url}" class="img-thumbnail" style="max-width:150px;">`
//             : '<p class="text-muted">No app logo uploaded</p>';
//     }
// }

// // Add school form submission
// function handleAddSchoolSubmit(e) {
//     e.preventDefault();

//     const errorMsg = document.getElementById("add-alert-error-msg");
//     if (errorMsg) {
//         errorMsg.classList.add("d-none");
//         errorMsg.innerHTML = "";
//     }

//     // Validation
//     if (!addNameField || !addNameField.value.trim()) {
//         showFormError('add', "Please enter a school name");
//         return false;
//     }
//     if (!addAddressField || !addAddressField.value.trim()) {
//         showFormError('add', "Please enter an address");
//         return false;
//     }
//     if (!addPhoneField || !addPhoneField.value.trim()) {
//         showFormError('add', "Please enter a phone number");
//         return false;
//     }
//     if (!addEmailField || !addEmailField.value.trim()) {
//         showFormError('add', "Please enter an email");
//         return false;
//     }
//     if (!addTimesOpenedField || !addTimesOpenedField.value) {
//         showFormError('add', "Please enter the number of times school opened");
//         return false;
//     }

//     // Prepare form data
//     const formData = new FormData();
//     formData.append('school_name', addNameField.value);
//     formData.append('school_address', addAddressField.value);
//     formData.append('school_phone', addPhoneField.value);
//     formData.append('school_email', addEmailField.value);
//     formData.append('school_motto', addMottoField.value);
//     formData.append('school_website', addWebsiteField.value);
//     formData.append('no_of_times_school_opened', addTimesOpenedField.value);
//     formData.append('date_school_opened', addDateOpenedField.value);
//     formData.append('date_next_term_begins', addNextTermField.value);
//     formData.append('is_active', addStatusField.checked ? 1 : 0);

//     // Add cropped school logo if available
//     if (croppedSchoolLogoBlob) {
//         const schoolLogoFile = new File([croppedSchoolLogoBlob], 'school_logo_cropped.png', { type: 'image/png' });
//         formData.append('school_logo', schoolLogoFile);
//     } else if (addLogoField && addLogoField.files.length > 0) {
//         // If no cropped image but original file exists
//         formData.append('school_logo', addLogoField.files[0]);
//     }

//     // Add cropped app logo if available
//     if (croppedAppLogoBlob) {
//         const appLogoFile = new File([croppedAppLogoBlob], 'app_logo_cropped.png', { type: 'image/png' });
//         formData.append('app_logo', appLogoFile);
//     } else {
//         const appLogoInput = document.getElementById('app_logo');
//         if (appLogoInput && appLogoInput.files.length > 0) {
//             formData.append('app_logo', appLogoInput.files[0]);
//         }
//     }

//     const csrfToken = document.querySelector('meta[name="csrf-token"]');
//     if (csrfToken) {
//         formData.append('_token', csrfToken.content);
//     }

//     // Submit
//     const addBtn = document.getElementById("add-btn");
//     const originalText = addBtn.innerHTML;
//     addBtn.innerHTML = '<i class="ri-loader-4-line spin me-2"></i> Adding...';
//     addBtn.disabled = true;

//     fetch('/school-info', {
//         method: 'POST',
//         headers: {
//             'Accept': 'application/json',
//             'X-Requested-With': 'XMLHttpRequest'
//         },
//         body: formData
//     })
//     .then(response => response.json())
//     .then(data => {
//         if (data.success) {
//             showSuccessAlert('School added successfully!');
//             setTimeout(() => window.location.reload(), 1500);
//         } else {
//             let errorMessage = data.message || 'Failed to add school';
//             if (data.errors) {
//                 errorMessage = Object.values(data.errors).flat().join(', ');
//             }
//             showFormError('add', errorMessage);
//         }
//     })
//     .catch(error => {
//         console.error('Error:', error);
//         showFormError('add', 'Network error. Please try again.');
//     })
//     .finally(() => {
//         addBtn.innerHTML = originalText;
//         addBtn.disabled = false;
//     });
// }

// // Edit school form submission
// // Edit school form submission - FIXED VERSION
// function handleEditSchoolSubmit(e) {
//     e.preventDefault();

//     console.log('Starting edit school submission...');

//     // Get CSRF token
//     const csrfToken = document.querySelector('meta[name="csrf-token"]');
//     if (!csrfToken) {
//         showAlert('error', 'Security Error', 'CSRF token not found. Please refresh the page.');
//         return;
//     }

//     const errorMsg = document.getElementById("edit-alert-error-msg");
//     if (errorMsg) {
//         errorMsg.classList.add("d-none");
//         errorMsg.innerHTML = "";
//     }

//     // Validation
//     const validations = [
//         { field: editNameField, message: "Please enter a school name" },
//         { field: editAddressField, message: "Please enter an address" },
//         { field: editPhoneField, message: "Please enter a phone number" },
//         { field: editEmailField, message: "Please enter an email" },
//         { field: editTimesOpenedField, message: "Please enter the number of times school opened" }
//     ];

//     for (const validation of validations) {
//         if (!validation.field || !validation.field.value.trim()) {
//             showFormError('edit', validation.message);
//             return false;
//         }
//     }

//     // Prepare form data
//     const formData = new FormData();
//     formData.append('school_name', editNameField.value.trim());
//     formData.append('school_address', editAddressField.value.trim());
//     formData.append('school_phone', editPhoneField.value.trim());
//     formData.append('school_email', editEmailField.value.trim());
//     formData.append('school_motto', editMottoField.value?.trim() || '');
//     formData.append('school_website', editWebsiteField.value?.trim() || '');
//     formData.append('no_of_times_school_opened', editTimesOpenedField.value);
//     formData.append('date_school_opened', editDateOpenedField.value || '');
//     formData.append('date_next_term_begins', editNextTermField.value || '');
//     formData.append('is_active', editStatusField.checked ? 1 : 0);
//     formData.append('_token', csrfToken.content);
//     formData.append('_method', 'PUT'); // Laravel method spoofing

//     // Add cropped school logo if available
//     if (croppedEditSchoolLogoBlob) {
//         const schoolLogoFile = new File([croppedEditSchoolLogoBlob], 'school_logo_cropped.png', { type: 'image/png' });
//         formData.append('school_logo', schoolLogoFile);
//         console.log('Adding cropped school logo');
//     } else if (editLogoField && editLogoField.files.length > 0) {
//         formData.append('school_logo', editLogoField.files[0]);
//         console.log('Adding original school logo file');
//     }

//     // Add cropped app logo if available
//     if (croppedEditAppLogoBlob) {
//         const appLogoFile = new File([croppedEditAppLogoBlob], 'app_logo_cropped.png', { type: 'image/png' });
//         formData.append('app_logo', appLogoFile);
//         console.log('Adding cropped app logo');
//     } else {
//         const editAppLogoInput = document.getElementById('edit_app_logo');
//         if (editAppLogoInput && editAppLogoInput.files.length > 0) {
//             formData.append('app_logo', editAppLogoInput.files[0]);
//             console.log('Adding original app logo file');
//         }
//     }

//     // Log what we're sending (without file content)
//     console.log('Sending update request for ID:', editIdField.value);
//     const formEntries = Array.from(formData.entries()).map(([key, value]) => {
//         if (value instanceof File) {
//             return [key, `File: ${value.name} (${value.size} bytes)`];
//         }
//         return [key, value];
//     });
//     console.log('Form data:', formEntries);

//     // Show loading state
//     const updateBtn = document.getElementById("update-btn");
//     const originalText = updateBtn.innerHTML;
//     updateBtn.innerHTML = '<i class="ri-loader-4-line spin me-2"></i> Updating...';
//     updateBtn.disabled = true;

//     // Make the fetch request
//     fetch(`/school-info/${editIdField.value}`, {
//         method: 'POST',
//         headers: {
//             'Accept': 'application/json',
//             'X-Requested-With': 'XMLHttpRequest',
//             'X-CSRF-TOKEN': csrfToken.content
//         },
//         credentials: 'same-origin', // Important for sessions
//         body: formData
//     })
//     .then(async response => {
//         console.log('Response status:', response.status, response.statusText);

//         // Get response as text first
//         const text = await response.text();

//         // Check for CSRF error
//         if (response.status === 419 || text.includes('419')) {
//             throw new Error('CSRF token mismatch. Please refresh the page and try again.');
//         }

//         // Try to parse as JSON
//         try {
//             const data = JSON.parse(text);
//             return { response, data };
//         } catch (e) {
//             console.error('Response is not valid JSON. Raw response:', text.substring(0, 500));

//             // Try to extract error message from HTML
//             let errorMessage = 'Server returned an error. ';

//             if (text.includes('Whoops')) {
//                 errorMessage += 'Internal server error.';
//             } else if (text.includes('404')) {
//                 errorMessage += 'Endpoint not found.';
//             } else if (text.includes('500')) {
//                 errorMessage += 'Server error.';
//             } else if (text.includes('validation')) {
//                 errorMessage += 'Validation error.';
//             }

//             throw new Error(errorMessage);
//         }
//     })
//     .then(({ response, data }) => {
//         if (!response.ok) {
//             throw new Error(`HTTP ${response.status}: ${data.message || 'Update failed'}`);
//         }

//         if (data.success) {
//             showSuccessAlert('School updated successfully!');
//             setTimeout(() => window.location.reload(), 1500);
//         } else {
//             let errorMessage = data.message || 'Failed to update school';
//             if (data.errors) {
//                 errorMessage = Object.values(data.errors).flat().join(', ');
//             }
//             throw new Error(errorMessage);
//         }
//     })
//     .catch(error => {
//         console.error('Update error:', error);

//         // Show user-friendly error
//         let userMessage = error.message;

//         if (error.message.includes('CSRF')) {
//             userMessage = 'Session expired. Please refresh the page and try again.';
//         } else if (error.message.includes('Network')) {
//             userMessage = 'Network error. Please check your connection.';
//         }

//         showFormError('edit', userMessage);

//         // For debugging - show more details
//         if (error.message.includes('Server returned an error')) {
//             console.warn('Check Laravel logs for more details:');
//             console.warn('1. Check storage/logs/laravel.log');
//             console.warn('2. Check browser Network tab for response');
//             console.warn('3. Verify CSRF token matches session');
//         }
//     })
//     .finally(() => {
//         updateBtn.innerHTML = originalText;
//         updateBtn.disabled = false;
//     });
// }

// // Delete record handler
// function handleDeleteRecord() {
//     const modal = document.getElementById("deleteRecordModal");
//     if (!modal) return;

//     const itemId = modal.dataset.itemId;

//     if (!itemId) {
//         showAlert('error', 'Error', 'No school selected for deletion');
//         return;
//     }

//     const deleteBtn = document.getElementById("delete-record");
//     const originalText = deleteBtn.innerHTML;
//     deleteBtn.innerHTML = '<i class="ri-loader-4-line spin me-2"></i> Deleting...';
//     deleteBtn.disabled = true;

//     fetch(`/school-info/${itemId}`, {
//         method: 'DELETE',
//         headers: {
//             'Accept': 'application/json',
//             'X-Requested-With': 'XMLHttpRequest',
//             'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
//         }
//     })
//     .then(response => response.json())
//     .then(data => {
//         if (data.success) {
//             showSuccessAlert('School deleted successfully!');
//             setTimeout(() => window.location.reload(), 1500);
//         } else {
//             showAlert('error', 'Error', data.message || 'Failed to delete school');
//         }
//     })
//     .catch(error => {
//         console.error('Error:', error);
//         showAlert('error', 'Error', 'Network error. Please try again.');
//     })
//     .finally(() => {
//         deleteBtn.innerHTML = originalText;
//         deleteBtn.disabled = false;
//         const bsModal = bootstrap.Modal.getInstance(modal);
//         if (bsModal) {
//             bsModal.hide();
//         }
//     });
// }

// // Modal close handler
// function handleModalClose(type) {
//     if (type === 'add') {
//         clearAddFields();
//         const addErrorMsg = document.getElementById('add-alert-error-msg');
//         if (addErrorMsg) {
//             addErrorMsg.classList.add('d-none');
//             addErrorMsg.innerHTML = '';
//         }

//         // Reset add modal croppers
//         if (schoolLogoCropper) {
//             schoolLogoCropper.destroy();
//             schoolLogoCropper = null;
//         }
//         if (appLogoCropper) {
//             appLogoCropper.destroy();
//             appLogoCropper = null;
//         }

//         // Clear stored blobs
//         croppedSchoolLogoBlob = null;
//         croppedAppLogoBlob = null;

//         const schoolLogoCropperContainer = document.getElementById('school-logo-cropper-container');
//         if (schoolLogoCropperContainer) schoolLogoCropperContainer.classList.add('d-none');

//         const appLogoCropperContainer = document.getElementById('app-logo-cropper-container');
//         if (appLogoCropperContainer) appLogoCropperContainer.classList.add('d-none');

//         const schoolLogoPreview = document.getElementById('school-logo-preview');
//         if (schoolLogoPreview) schoolLogoPreview.innerHTML = '';

//         const appLogoPreview = document.getElementById('app-logo-preview');
//         if (appLogoPreview) appLogoPreview.innerHTML = '';

//     } else if (type === 'edit') {
//         clearEditFields();
//         const editErrorMsg = document.getElementById('edit-alert-error-msg');
//         if (editErrorMsg) {
//             editErrorMsg.classList.add('d-none');
//             editErrorMsg.innerHTML = '';
//         }

//         // Reset edit modal croppers
//         if (editSchoolLogoCropper) {
//             editSchoolLogoCropper.destroy();
//             editSchoolLogoCropper = null;
//         }
//         if (editAppLogoCropper) {
//             editAppLogoCropper.destroy();
//             editAppLogoCropper = null;
//         }

//         // Clear stored blobs
//         croppedEditSchoolLogoBlob = null;
//         croppedEditAppLogoBlob = null;

//         const editSchoolLogoCropperContainer = document.getElementById('edit-school-logo-cropper-container');
//         if (editSchoolLogoCropperContainer) editSchoolLogoCropperContainer.classList.add('d-none');

//         const editAppLogoCropperContainer = document.getElementById('edit-app-logo-cropper-container');
//         if (editAppLogoCropperContainer) editAppLogoCropperContainer.classList.add('d-none');

//         const editSchoolLogoPreview = document.getElementById('edit-school-logo-preview');
//         if (editSchoolLogoPreview) editSchoolLogoPreview.innerHTML = '';

//         const editAppLogoPreview = document.getElementById('edit-app-logo-preview');
//         if (editAppLogoPreview) editAppLogoPreview.innerHTML = '';
//     }
// }

// // Helper functions
// function clearAddFields() {
//     if (addIdField) addIdField.value = "";
//     if (addNameField) addNameField.value = "";
//     if (addAddressField) addAddressField.value = "";
//     if (addPhoneField) addPhoneField.value = "";
//     if (addEmailField) addEmailField.value = "";
//     if (addLogoField) addLogoField.value = "";
//     if (addMottoField) addMottoField.value = "";
//     if (addWebsiteField) addWebsiteField.value = "";
//     if (addTimesOpenedField) addTimesOpenedField.value = "";
//     if (addDateOpenedField) addDateOpenedField.value = "";
//     if (addNextTermField) addNextTermField.value = "";
//     if (addStatusField) addStatusField.checked = false;

//     // Clear app logo input
//     const appLogoInput = document.getElementById('app_logo');
//     if (appLogoInput) appLogoInput.value = "";
// }

// function clearEditFields() {
//     if (editIdField) editIdField.value = "";
//     if (editNameField) editNameField.value = "";
//     if (editAddressField) editAddressField.value = "";
//     if (editPhoneField) editPhoneField.value = "";
//     if (editEmailField) editEmailField.value = "";
//     if (editLogoField) editLogoField.value = "";
//     if (editMottoField) editMottoField.value = "";
//     if (editWebsiteField) editWebsiteField.value = "";
//     if (editTimesOpenedField) editTimesOpenedField.value = "";
//     if (editDateOpenedField) editDateOpenedField.value = "";
//     if (editNextTermField) editNextTermField.value = "";
//     if (editStatusField) editStatusField.checked = false;

//     // Clear app logo input
//     const editAppLogoInput = document.getElementById('edit_app_logo');
//     if (editAppLogoInput) editAppLogoInput.value = "";
// }

// function showFormError(type, message) {
//     const errorDiv = document.getElementById(`${type}-alert-error-msg`);
//     if (errorDiv) {
//         errorDiv.innerHTML = `<i class="ri-error-warning-line me-2"></i> ${message}`;
//         errorDiv.classList.remove('d-none');
//     }
// }

// function showAlert(icon, title, text) {
//     if (typeof Swal !== 'undefined') {
//         Swal.fire({
//             icon: icon,
//             title: title,
//             text: text,
//             showConfirmButton: true
//         });
//     } else {
//         alert(`${title}: ${text}`);
//     }
// }

// function showSuccessAlert(message) {
//     if (typeof Swal !== 'undefined') {
//         Swal.fire({
//             position: "center",
//             icon: "success",
//             title: message,
//             showConfirmButton: false,
//             timer: 2000,
//             showCloseButton: true
//         });
//     } else {
//         alert(message);
//     }
// }

// function showLoading(show, type = 'add') {
//     if (type === 'edit') {
//         const editModalLabel = document.getElementById('editModalLabel');
//         if (editModalLabel) {
//             editModalLabel.innerHTML = show
//                 ? '<i class="ri-loader-4-line spin me-2"></i> Loading...'
//                 : 'Edit School';
//         }
//     }
// }

// function filterData() {
//     var searchInput = document.querySelector(".search-box input.search");
//     if (!searchInput) return;

//     var searchValue = searchInput.value.toLowerCase();
//     var statusSelect = document.getElementById("idStatus");
//     var emailSelect = document.getElementById("idEmail");
//     var selectedStatus = statusSelect ? statusSelect.value : "all";
//     var selectedEmail = emailSelect ? emailSelect.value : "all";

//     console.log("Filtering with:", { search: searchValue, status: selectedStatus, email: selectedEmail });

//     schoolList.filter(function (item) {
//         var nameMatch = item.values().name.toLowerCase().includes(searchValue);
//         var emailMatch = item.values().email.toLowerCase().includes(searchValue);
//         var statusMatch = selectedStatus === "all" || item.values().status === selectedStatus;
//         var emailSelectMatch = selectedEmail === "all" || item.values().email === selectedEmail;

//         return (nameMatch || emailMatch) && statusMatch && emailSelectMatch;
//     });
// }

// function deleteMultiple() {
//     const ids_array = [];
//     const checkboxes = document.querySelectorAll('tbody input[name="chk_child"]');
//     checkboxes.forEach((checkbox) => {
//         if (checkbox.checked) {
//             const id = checkbox.closest("tr").querySelector(".id").getAttribute("data-id");
//             ids_array.push(id);
//         }
//     });

//     if (ids_array.length === 0) {
//         showAlert('info', 'Please select', 'Please select at least one school to delete');
//         return;
//     }

//     if (typeof Swal !== 'undefined') {
//         Swal.fire({
//             title: 'Are you sure?',
//             text: `You are about to delete ${ids_array.length} school(s). This action cannot be undone.`,
//             icon: 'warning',
//             showCancelButton: true,
//             confirmButtonText: 'Yes, delete it!',
//             cancelButtonText: 'Cancel'
//         }).then((result) => {
//             if (result.isConfirmed) {
//                 // Implement bulk delete if needed
//                 console.log("Bulk delete would delete IDs:", ids_array);
//                 showAlert('info', 'Bulk Delete', 'Bulk delete functionality would be implemented here');
//             }
//         });
//     }
// }

// // Export functions that need to be accessible from HTML
// window.filterData = filterData;
// window.deleteMultiple = deleteMultiple;

// var perPage = 100; // Pagination set to 100 items per page

// // Build valueNames and item template dynamically based on subjects
// var valueNames = ['sn', 'admissionno', 'name', 'gender', 'teacher-comment', 'guidance-comment'];
// if (window.subjects && Array.isArray(window.subjects)) {
//     window.subjects.forEach(function(subject) {
//         valueNames.push('subject-' + subject);
//     });
// }

// var itemTemplate = '<tr>' +
//     '<td class="sn"></td>' +
//     '<td class="admissionno" data-admissionno></td>' +
//     '<td class="name" data-name><div class="d-flex align-items-center"><img src="" alt="Student Picture" class="rounded-circle avatar-sm student-image" data-bs-toggle="modal" data-bs-target="#imageViewModal" data-image="" data-admissionno="" data-file-exists="false" data-default-exists="false" /><div class="ms-3"><h6 class="mb-0"><a href="#" class="text-reset"></a></h6></div></div></td>' +
//     '<td class="gender" data-gender></td>';

// if (window.subjects && Array.isArray(window.subjects)) {
//     window.subjects.forEach(function(subject) {
//         itemTemplate += '<td class="subject-' + subject + '" data-subject-' + subject + ' align="center" style="font-size: 14px;"></td>';
//     });
// }

// itemTemplate += '<td class="teacher-comment"><input type="text" class="form-control teacher-comment-input" name="teacher_comments[0]" data-teacher-comment="" placeholder="Enter teacher\'s comment"></td>' +
//     '<td class="guidance-comment"><input type="text" class="form-control guidance-comment-input" name="guidance_comments[0]" data-guidance-comment="" placeholder="Enter guidance counselor\'s comment"></td>' +
//     '</tr>';

// // Define List.js options
// var options = {
//     valueNames: valueNames,
//     page: perPage,
//     pagination: true,
//     item: itemTemplate
// };

// // Initialize List.js
// var studentList = new List('studentListTable', options);

// console.log("Initial studentList items:", studentList.items.length);

// // Update pagination and no-result display on list update
// studentList.on("updated", function (e) {
//     console.log("List.js updated, matching items:", e.matchingItems.length, "total items:", studentList.items.length);
//     const noResultElement = document.querySelector(".noresult");
//     if (noResultElement) {
//         noResultElement.style.display = e.matchingItems.length === 0 ? "block" : "none";
//     } else {
//         console.warn("No element with class 'noresult' found in the DOM");
//     }
//     document.getElementById("pagination-showing").innerText = e.matchingItems.length;
//     document.getElementById("pagination-total").innerText = studentList.items.length;
// });

// // Handle DOM content loaded
// document.addEventListener("DOMContentLoaded", function () {
//     console.log("DOM loaded, initializing List.js...");
//     console.log("Initial studentList items:", studentList.items.length);
//     document.getElementById("pagination-showing").innerText = Math.min(perPage, studentList.items.length);
//     document.getElementById("pagination-total").innerText = studentList.items.length;
// });

// // Filter data based on search input
// function filterData() {
//     var searchInput = document.querySelector(".search-box input.search")?.value.toLowerCase() || '';
//     console.log("Filtering with search:", searchInput);

//     studentList.filter(function (item) {
//         var nameMatch = item.values().name.toLowerCase().includes(searchInput);
//         var admissionNoMatch = item.values().admissionno.toLowerCase().includes(searchInput);
//         var teacherCommentMatch = item.values()['teacher-comment'].toLowerCase().includes(searchInput);
//         var guidanceCommentMatch = item.values()['guidance-comment'].toLowerCase().includes(searchInput);
//         return nameMatch || admissionNoMatch || teacherCommentMatch || guidanceCommentMatch;
//     });
// }

// // Attach filter event listener if search box exists
// var searchBox = document.querySelector(".search-box input.search");
// if (searchBox) {
//     searchBox.addEventListener("input", filterData);
// }

// // Update List.js values when input fields change
// document.addEventListener('input', function (e) {
//     if (e.target.classList.contains('teacher-comment-input') || e.target.classList.contains('guidance-comment-input')) {
//         var row = e.target.closest('tr');
//         var studentId = row.querySelector('.admissionno').getAttribute('data-admissionno');
//         var item = studentList.get('admissionno', studentId)[0];
//         if (item) {
//             if (e.target.classList.contains('teacher-comment-input')) {
//                 item.values()['teacher-comment'] = e.target.value || 'N/A';
//             } else if (e.target.classList.contains('guidance-comment-input')) {
//                 item.values()['guidance-comment'] = e.target.value || 'N/A';
//             }
//         }
//     }
// });

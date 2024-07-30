jQuery(document).ready(($) => {
  loadMenuItems(0, 1);

  function loadMenuItems(categoryId, page) {
    $("#loading-indicator").show();
    $("#menu-container").hide();

    $.ajax({
      url: ajax_object.ajax_url,
      type: "POST",
      data: {
        action: "fetch_menu_items",
        category_id: categoryId,
        paged: page,
      },
      success: function (response) {
        $("#loading-indicator").hide();
        $("#menu-container").show();

        if (response.success) {
          let menuHtml = "";

          // Loop through each child category
          $.each(response.data, function (categoryId, categoryData) {
            menuHtml += `<div class="menu-category-container">`;
            menuHtml += `<div>`;
            menuHtml += `<h3>${categoryData.category_name}</h3>`;

            // Loop through items in this category
            $.each(categoryData.items, function (index, item) {
              menuHtml += `
                <div class="menu-item">
                  <div class ="main-content">
                    <div class="details">
                        <p>${item.title}</p>
                        ${
                          item.description
                            ? `<span class="description">${item.description}</span>`
                            : ""
                        }
                        <div class="allergens">
                        ${
                          item.allergen_advisory
                            ? item.allergen_advisory
                                .map(
                                  (image_url, index) =>
                                    `<img src="${image_url}" alt="Allergen Advisory" />`
                                )
                                .join("")
                            : ""
                        }
                        </div>
                         
                    </div>
                    <div class="menu-item-price">
                        ${
                          item.normal_price && item.normal_price != 0
                            ? `<span title="Normal Price">${item.normal_price}</span>`
                            : ""
                        }
                        ${
                          item.medium_price && item.medium_price != 0
                            ? `<span title="Medium Price">${item.medium_price}</span>`
                            : ""
                        }
                        ${
                          item.large_price && item.large_price != 0
                            ? `<span title="Large Price">${item.large_price}</span>`
                            : ""
                        }
                        ${
                          item.half_serve && item.half_serve != 0
                            ? `<span title="Half Serve">${item.half_serve}</span>`
                            : ""
                        }
                        ${
                          item.full_serve && item.full_serve != 0
                            ? `<span title="Full Serve">${item.full_serve}</span>`
                            : ""
                        }
                        ${
                          item.single && item.single != 0
                            ? `<span title="Single">${item.single}</span>`
                            : ""
                        }
                        ${
                          item.double && item.double != 0
                            ? `<span title="Double">${item.double}</span>`
                            : ""
                        }
                        ${
                          item.triple && item.triple != 0
                            ? `<span title="Triple">${item.triple}</span>`
                            : ""
                        }
                        ${
                          item.double_dish && item.double_dish != 0
                            ? `<span title="Double Dish">${item.double_dish}</span>`
                            : ""
                        }
                        ${
                          item.triple_dish && item.triple_dish != 0
                            ? `<span title="Triple dish">${item.triple_dish}</span>`
                            : ""
                        }
                        ${
                          item.five_pieces && item.five_pieces != 0
                            ? `<span title="5 pieces">${item.five_pieces}</span>`
                            : ""
                        }
                        ${
                          item.ten_pieces && item.ten_pieces != 0
                            ? `<span title="10 pieces">${item.ten_pieces}</span>`
                            : ""
                        }
                    </div>
                  </div>
                 </div>
                `;
            });
            menuHtml += `</div>`;
            menuHtml += `
                ${
                  categoryData.category_thumbnail
                    ? `<div class="category_thumbnail"><img src=${categoryData.category_thumbnail} alt="${categoryData.category_name}"></div>`
                    : ""
                }
            `;
            menuHtml += `</div>`; // Close menu-category-container
          });

          $("#menu-container").html(menuHtml);
        } else {
          $("#menu-container").html("<p>" + response.data + "</p>");
        }
      },
    });
  }

  $(document).on("click", ".menu-category", function () {
    const categoryId = $(this).data("category-id");
    loadMenuItems(categoryId, 1);
  });

  // Pagination (if you have implemented)
  $(document).on("click", ".menu-pagination a", function (e) {
    e.preventDefault();
    const categoryId = $("#menu-categories .active").data("category-id");
    const page = $(this).data("page");
    loadMenuItems(categoryId, page);
  });
});

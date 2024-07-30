jQuery(document).ready(($) => {
  $(".pantry-category").click(function () {
    $(".pantry-category").removeClass("active");
    $(this).addClass("active");
  });

  // Initial load of menu items (assuming category_id 0 for all items)
  loadMenuItems(0, 1);

  function loadMenuItems(categoryId, page) {
    $("#loading-indicator").show();
    $("#pantry-container").hide();

    $.ajax({
      url: ajax_object.ajax_url,
      type: "POST",
      data: {
        action: "fetch_pantry_items",
        category_id: categoryId,
        paged: page,
      },
      success: function (response) {
        $("#loading-indicator").hide();
        $("#pantry-container").show();

        if (response.success) {
          let menuHtml = "";

          $.each(response.data, function (index, item) {
            menuHtml += `
              <div class="pantry-item">
                <div class="main-content">
                    <h2>${item.title}</h2>
                    <p>
                    ${item.content ? `${item.content}` : ""}
                    </p>
                    <div>
                    ${
                      item.nutritional_facts
                        ? `<img src="${item.nutritional_facts}" />`
                        : ""
                    }
                   </div>
                   ${
                    item.grind_size && item.gourmet>0
                      ? `
                      <div class="details">
                   <div>
                        ${
                          item.gourmet
                            ? `<span class="bold">GOURMET</span><span >${item.gourmet} gms</span> `
                            : ""
                        }
                         </div>
                    <div>
                        ${
                          item.grind_size
                            ? `<span class="bold">Grind Size</span><span >${item.grind_size}</span>`
                            : ""
                        }
                        </div>
                  </div>
                      
                      `
                      : ""
                  }
                   
                  <div class="details">
                  <div>
                    ${
                      item.region
                        ? `<span class="bold">Region</span><img src="${item.region_flag}" /><span> ${item.region}</span> `
                        : ""
                    }
                    </div>
                    <div>
                    ${
                      item.tasting_notes
                        ? `<span class="bold">Tasting Notes</span><span>${item.tasting_notes}</span>`
                        : ""
                    }
                     </div>
                    <div>
                    ${
                      item.intensity
                        ? `<span class="bold">Intensity</span><div id="rating-container" >${createStarRating(item.intensity)}</div>`
                        : ""
                    }
                    </div>
                  </div>

                  <div class="cta">
                    ${
                      item.available >0
                        ? `<button class="nectar-button large regular regular-button available" role="button" href="" >Available in ${item.available}</button>`
                        : ""
                    }
                    ${
                      item.recipes
                        ? `<a class="nectar-button large regular regular-button recipes" role="button" href="${item.recipes}" >Recipes</a>`
                        : ""
                    }
                    ${
                      item.learn
                        ? `<a class="nectar-button large regular regular-button learn" role="button" href="${item.learn}" >Learn about coffee and the different blends</a>`
                        : ""
                    }
                  </div>
                </div>
                  <div class="pantry-images">
                    ${
                      item.featured_image
                        ? `<img src="${item.featured_image}" >`
                        : ""
                    }
                  </div>
              </div>
            `;
          });

          $("#pantry-container").html(menuHtml);
        } else {
          console.error("Error fetching pantry items:", response.data);
          $("#pantry-container").html(
            "<p class='response'>" + response.data + "</p>"
          );
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX Error:", status, error);
        $("#loading-indicator").hide();
        $("#pantry-container").html(
          "<p>Failed to load pantry items. Please try again.</p>"
        );
      },
    });
  }

  // Handle click on a pantry category (if you have category selection)
  $(document).on("click", ".pantry-category", function () {
    const categoryId = $(this).data("category-id");
    loadMenuItems(categoryId, 1);
  });

  // Pagination click event (if implemented in HTML)
  $(document).on("click", ".pantry-pagination a", function (e) {
    e.preventDefault();
    const categoryId = $(".pantry-category.active").data("category-id");
    const page = $(this).data("page");
    loadMenuItems(categoryId, page);
  });

  function createStarRating(intensity, maxRating = 6) {
    let stars = "";

    // Full stars
    for (let i = 0; i < Math.floor(intensity); i++) {
      stars += "★"; // Filled star
    }

    // Half star
    if (intensity % 1 !== 0) {
      stars += "✩"; // Half star
    }

    // Empty stars
    for (let i = Math.ceil(intensity); i < maxRating; i++) {
      stars += "☆"; // Empty star
    }

    return stars;
  }

  $("#rating-container").html(`<div>${intensityHtml}</div>`);
});

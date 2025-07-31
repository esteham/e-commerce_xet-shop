<div class="eshop-sidebar">
    <h4 class="sidebar-title"><a href="index.php">Categories</a></h4>
    <div class="category-list">
        <?php
        $current_cat_id = isset($_GET['cat_id']) ? $_GET['cat_id'] : null;
        $current_subcat_id = isset($_GET['sub_cat_id']) ? $_GET['sub_cat_id'] : null;
        
        $categoriesStmt = $DB_con->runQuery("SELECT * FROM categories ORDER BY category_name ASC");
        $categoriesStmt->execute();
        
        while($category = $categoriesStmt->fetch(PDO::FETCH_ASSOC)) {
            $is_active = ($current_cat_id == $category['id'] && !$current_subcat_id);
            $sub_cats = $DB_con->runQuery("SELECT * FROM sub_categories WHERE category_id = :cat_id ORDER BY sub_cat_name ASC");
            $sub_cats->execute(array(':cat_id' => $category['id']));
            
            if($sub_cats->rowCount() > 0) {
                $has_active_child = false;
                $subcats_html = '';
                
                while($sub_cat = $sub_cats->fetch(PDO::FETCH_ASSOC)) {
                    $is_sub_active = ($current_subcat_id == $sub_cat['id']);
                    $has_active_child = $has_active_child || $is_sub_active;
                    $active_class = $is_sub_active ? 'active' : '';
                    $subcats_html .= '<a href="?sub_cat_id='.$sub_cat['id'].'" class="subcategory-item '.$active_class.'">
                                        '.htmlspecialchars($sub_cat['sub_cat_name']).'
                                      </a>';
                }
                
                $category_class = ($is_active || $has_active_child) ? 'active' : '';
                $submenu_class = ($is_active || $has_active_child) ? 'show' : '';
                
                echo '<div class="category-item has-submenu '.$category_class.'">
                        <div class="category-header" onclick="toggleSubmenu(this)">
                            <span>'.htmlspecialchars($category['category_name']).'</span>
                            <i class="fas fa-chevron-'.($submenu_class ? 'up' : 'down').' arrow"></i>
                        </div>
                        <div class="subcategory-list '.$submenu_class.'">
                            '.$subcats_html.'
                        </div>
                      </div>';
            } else {
                $active_class = $is_active ? 'active' : '';
                echo '<a href="?cat_id='.$category['id'].'" class="category-item '.$active_class.'">
                        '.htmlspecialchars($category['category_name']).'
                      </a>';
            }
        }
        ?>
    </div>
</div>

<style>
.eshop-sidebar {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    padding: 20px;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.sidebar-title {
    color: #2c3e50;
    font-weight: 600;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.category-list {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.category-item {
    color: #34495e;
    text-decoration: none;
    padding: 12px 15px;
    border-radius: 6px;
    transition: all 0.3s ease;
    font-weight: 500;
}

.category-item:hover {
    background: #f8f9fa;
    color: #3498db;
}

.category-item.active {
    background: #e3f2fd;
    color: #1976d2;
    font-weight: 600;
}

.has-submenu {
    display: flex;
    flex-direction: column;
    padding: 0;
}

.has-submenu.active > .category-header {
    background: #e3f2fd;
    color: #1976d2;
    font-weight: 600;
}

.category-header {
    padding: 12px 15px;
    border-radius: 6px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    transition: all 0.3s ease;
}

.category-header:hover {
    background: #f8f9fa;
    color: #3498db;
}

.category-header .arrow {
    transition: all 0.3s ease;
}

.subcategory-list {
    max-height: 0;
    overflow: hidden;
    opacity: 0;
    transition: max-height 0.3s ease, opacity 0.3s ease;
    padding-left: 15px;
}

.subcategory-list.show {
    max-height: 500px;
    opacity: 1;
}

.subcategory-item {
    display: block;
    padding: 10px 15px;
    color: #7f8c8d;
    text-decoration: none;
    border-radius: 6px;
    transition: all 0.3s ease;
    font-size: 14px;
    font-weight: 400;
}

.subcategory-item:hover {
    background: #f1f8fe;
    color: #3498db;
    padding-left: 20px;
}

.subcategory-item.active {
    background: #e3f2fd;
    color: #1976d2;
    font-weight: 500;
    padding-left: 20px;
}
.eshop-sidebar {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    padding: 20px;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;

    /* The following 3 lines make the sidebar sticky */
    position: sticky;
    top: 20px; /* Distance from the top when scrolling */
    z-index: 10; /* Ensures the sidebar stays above other elements */
}

</style>

<script>
function toggleSubmenu(element) {
    const parent = element.parentElement;
    const submenu = parent.querySelector('.subcategory-list');
    const arrow = element.querySelector('.arrow');
    
    submenu.classList.toggle('show');
    arrow.classList.toggle('fa-chevron-down');
    arrow.classList.toggle('fa-chevron-up');
    
    // Toggle active class on parent category
    parent.classList.toggle('active');
    
    // Close other open submenus if needed
    document.querySelectorAll('.subcategory-list').forEach(item => {
        if(item !== submenu && item.classList.contains('show')) {
            item.classList.remove('show');
            const otherParent = item.closest('.has-submenu');
            otherParent.classList.remove('active');
            const otherArrow = otherParent.querySelector('.arrow');
            otherArrow.classList.remove('fa-chevron-up');
            otherArrow.classList.add('fa-chevron-down');
        }
    });
}

// Automatically expand parent if a subcategory is active on page load
document.addEventListener('DOMContentLoaded', function() {
    const activeSubcats = document.querySelectorAll('.subcategory-item.active');
    activeSubcats.forEach(subcat => {
        const submenu = subcat.closest('.subcategory-list');
        if(submenu) {
            submenu.classList.add('show');
            const parent = submenu.closest('.has-submenu');
            parent.classList.add('active');
            const arrow = parent.querySelector('.arrow');
            arrow.classList.remove('fa-chevron-down');
            arrow.classList.add('fa-chevron-up');
        }
    });
});
</script>
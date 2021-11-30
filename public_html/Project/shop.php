
<?php
require(__DIR__ . "/../../partials/nav.php");

$results = filter($_POST['Submit']);
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js" integrity="sha512-894YE6QWD5I59HgZOGReFYm4dnWc1Qt5NtvYSaNcOP+u1T9qYdvdihz0PPSiiqn/+/3e7Jo4EaG7TubfWGUrMQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
    $document.ready(function() {
        $('#add_to_cart').submit(function(e) {

            $.ajax({
                type: "POST",
                url: "cart.php",
                data: $("#add_to_cart").serialize(),
                success: function(data) {
                    flash("Successfully Added To Cart!")
                }
            })

            e.preventDefault();
        })
    })
</script>
<div class="container-fluid">
    <h1>Shop</h1>
    <div class="sort" style="float:right;">
            <form id="s" method="post">
                <select name="size">
                    <option value="cat">Category</option>
                    <option value="high">Price: High to Low</option>
                    <option value="low">Price: Low to High</option>
                </select>
                <input type="submit" name="Submit" value="Send">
            </form>
</div>
    <div class="row row-cols-1 row-cols-md-5 g-4">
        
    
        
        <?php foreach ($results as $item) : ?>
            <form id="add_to_cart" action="cart.php?" method="POST">
                <div class="col">
                    <div class="card bg-light">
                        <div class="card-header">
                            <a href="admin/edit_item.php?id=<?php se($item, "id"); ?>"> EDIT: <?php se($item, "name"); ?></a>
                            <a href="product_details.php?id=<?php se($item, "id"); ?>"><?php se($item, "name"); ?></a>
                        </div>
                        <div class="card-body">
                            <div class="product-image">
                                
                            </div>
                            <h5 class="card-title"> <?php se($item, "name"); ?></h5>
                            <p class="card-text"><?php se($item, "description"); ?>...</p>
                        </div>
                        <div class="card-footer">
                            Cost: $<?php se($item, "unit_price"); ?>
                            <!--<button onclick="purchase('<?php //se($item, 'id'); 
                                                            ?>')" class="btn btn-primary">Add To Cart</button> -->
                            <button name=add_to_cart type="submit" class="btn btn-info">Add To Cart</button>
                        </div>
                    </div>
                </div>
                <input type="number" id="quantity" class="form-control mb-3" name="quantity" value="1">
                <input type="hidden" id="id" name="id" value="<?php se($item, "id"); ?>">
                <input type="hidden" id="name" name="name" value="<?php se($item, "name"); ?>">
                <input type="hidden" id="description" name="description" value="<?php se($item, "description"); ?>">
                <input type="hidden" id="unit_price" name="unit_price" value="<?php se($item, "unit_price"); ?>">
            <?php endforeach; ?>
    </div>
    </form>
</div>
<?php
require(__DIR__ . "/../../partials/footer.php");
?>
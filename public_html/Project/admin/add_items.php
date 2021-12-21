<?php
require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin") && !has_role("Owner")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: $BASE_PATH" . "home.php"));
}
if (isset($_POST["submit"])) {
    $id = save_data("Products", $_POST);
    if ($id > 0) {
        flash("Created Item with id $id", "success");
    }
}

$columns = get_columns("Products");

$ignore = ["id", "modified", "created", "avg_rating","Cart_Id"];
?>
<div class="container-fluid">
    <h1>Add Item</h1>
    <form method="POST">
        <?php foreach ($columns as $index => $column) : ?>
            <?php if (!in_array($column["Field"], $ignore)) : ?>
                <div class="mb-4">
                    <label class="form-label" for="<?php se($column, "Field"); ?>"><?php se($column, "Field"); ?></label>
                    <input class="form-control" id="<?php se($column, "Field"); ?>" type="<?php echo inputMap(se($column, "Type", "", false)); ?>" name="<?php se($column, "Field"); ?>" />
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
        <input class="btn btn-primary" type="submit" value="Create" name="submit" />
    </form>
</div>
<?php
require_once(__DIR__ . "/../../../partials/footer.php");
?>
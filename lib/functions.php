<?php
require_once(__DIR__ . "/db.php");
$BASE_PATH = '/Project/'; //This is going to be a helper for redirecting to our base project path since it's nested in another folder
function se($v, $k = null, $default = "", $isEcho = true)
{
    if (is_array($v) && isset($k) && isset($v[$k])) {
        $returnValue = $v[$k];
    } else if (is_object($v) && isset($k) && isset($v->$k)) {
        $returnValue = $v->$k;
    } else {
        $returnValue = $v;
        //added 07-05-2021 to fix case where $k of $v isn't set
        //this is to kep htmlspecialchars happy
        if (is_array($returnValue) || is_object($returnValue)) {
            $returnValue = $default;
        }
    }
    if (!isset($returnValue)) {
        $returnValue = $default;
    }
    if ($isEcho) {
        //https://www.php.net/manual/en/function.htmlspecialchars.php
        echo htmlspecialchars($returnValue, ENT_QUOTES);
    } else {
        //https://www.php.net/manual/en/function.htmlspecialchars.php
        return htmlspecialchars($returnValue, ENT_QUOTES);
    }
}
//TODO 2: filter helpers
function sanitize_email($email = "")
{
    return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
}
function is_valid_email($email = "")
{
    return filter_var(trim($email), FILTER_VALIDATE_EMAIL);
}
//TODO 3: User Helpers
function is_logged_in($redirect = false, $destination = "login.php")
{
    $isLoggedIn = isset($_SESSION["user"]);
    if ($redirect && !$isLoggedIn) {
        flash("You must be logged in to view this page", "warning");
        die(header("Location: $destination"));
    }
    return $isLoggedIn; //se($_SESSION, "user", false, false);
}
function has_role($role)
{
    if (is_logged_in() && isset($_SESSION["user"]["roles"])) {
        foreach ($_SESSION["user"]["roles"] as $r) {
            if ($r["name"] === $role) {
                return true;
            }
        }
    }
    return false;
}
function get_username()
{
    if (is_logged_in()) { //we need to check for login first because "user" key may not exist
        return se($_SESSION["user"], "username", "", false);
    }
    return "";
}
function get_user_email()
{
    if (is_logged_in()) { //we need to check for login first because "user" key may not exist
        return se($_SESSION["user"], "email", "", false);
    }
    return "";
}
function get_user_id()
{
    if (is_logged_in()) { //we need to check for login first because "user" key may not exist
        return se($_SESSION["user"], "id", false, false);
    }
    return false;
}
//TODO 4: Flash Message Helpers
function flash($msg = "", $color = "info")
{
    $message = ["text" => $msg, "color" => $color];
    if (isset($_SESSION['flash'])) {
        array_push($_SESSION['flash'], $message);
    } else {
        $_SESSION['flash'] = array();
        array_push($_SESSION['flash'], $message);
    }
}

function getMessages()
{
    if (isset($_SESSION['flash'])) {
        $flashes = $_SESSION['flash'];
        $_SESSION['flash'] = array();
        return $flashes;
    }
    return array();
}
//TODO generic helpers
function reset_session()
{
    session_unset();
    session_destroy();
}
function users_check_duplicate($errorInfo)
{
    if ($errorInfo[1] === 1062) {
        //https://www.php.net/manual/en/function.preg-match.php
        preg_match("/Users.(\w+)/", $errorInfo[2], $matches);
        if (isset($matches[1])) {
            flash("The chosen " . $matches[1] . " is not available.", "warning");
        } else {
            //TODO come up with a nice error message
            flash("<pre>" . var_export($errorInfo, true) . "</pre>");
        }
    } else {
        //TODO come up with a nice error message
        flash("<pre>" . var_export($errorInfo, true) . "</pre>");
    }
}
function get_url($dest)
{
    global $BASE_PATH;
    if (str_starts_with($dest, "/")) {
        //handle absolute path
        return $dest;
    }
    //handle relative path
    return $BASE_PATH . $dest;
}
function get_columns($table)
{
    $table = se($table, null, null, false);
    $db = getDB();
    $query = "SHOW COLUMNS from $table"; //be sure you trust $table
    $stmt = $db->prepare($query);
    $results = [];
    try {
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "<pre>" . var_export($e, true) . "</pre>";
    }
    return $results;
}
function save_data($table, $data, $ignore = ["submit"])
{
    $table = se($table, null, null, false);
    $db = getDB();
    $query = "INSERT INTO $table "; //be sure you trust $table
    //https://www.php.net/manual/en/functions.anonymous.php Example#3
    $columns = array_filter(array_keys($data), function ($x) use ($ignore) {
        return !in_array($x, $ignore); // $x !== "submit";
    });
    //arrow function uses fn and doesn't have return or { }
    //https://www.php.net/manual/en/functions.arrow.php
    $placeholders = array_map(fn ($x) => ":$x", $columns);
    $query .= "(" . join(",", $columns) . ") VALUES (" . join(",", $placeholders) . ")";

    $params = [];
    foreach ($columns as $col) {
        $params[":$col"] = $data[$col];
    }
    $stmt = $db->prepare($query);
    try {
        $stmt->execute($params);
        //https://www.php.net/manual/en/pdo.lastinsertid.php
        //echo "Successfully added new record with id " . $db->lastInsertId();
        return $db->lastInsertId();
    } catch (PDOException $e) {
        //echo "<pre>" . var_export($e->errorInfo, true) . "</pre>";
        flash("<pre>" . var_export($e->errorInfo, true) . "</pre>");
        return -1;
    }
}
function inputMap($fieldType)
{
    if (str_contains($fieldType, "varchar")) {
        return "text";
    } else if ($fieldType === "text") {
        return "textarea";
    } else if (in_array($fieldType, ["int", "decimal"])) { //TODO fill in as needed
        return "number";
    }
    return "text"; //default
}
function update_data($table, $id,  $data, $ignore = ["id", "submit"])
{
    $columns = array_keys($data);
    foreach ($columns as $index => $value) {
        //Note: normally it's bad practice to remove array elements during iteration

        //remove id, we'll use this for the WHERE not for the SET
        //remove submit, it's likely not in your table
        if (in_array($value, $ignore)) {
            unset($columns[$index]);
        }
    }
    $query = "UPDATE $table SET "; //be sure you trust $table
    $cols = [];
    foreach ($columns as $index => $col) {
        array_push($cols, "$col = :$col");
    }
    $query .= join(",", $cols);
    $query .= " WHERE id = :id";

    $params = [":id" => $id];
    foreach ($columns as $col) {
        $params[":$col"] = se($data, $col, "", false);
    }
    $db = getDB();
    $stmt = $db->prepare($query);
    try {
        $stmt->execute($params);
        return true;
    } catch (PDOException $e) {
        flash("<pre>" . var_export($e->errorInfo, true) . "</pre>");
        return false;
    }
}
function filter()
{
    $results = [];
    $db = getDB();
    //Sort and Filters
    $col = se($_GET, "col", "cost", false);
    //allowed list
    if (!in_array($col, ["unit_price", "category", "name"])) {
        $col = "unit_price"; //default value, prevent sql injection
    }
    $order = se($_GET, "order", "asc", false);
    //allowed list
    if (!in_array($order, ["asc", "desc"])) {
        $order = "asc"; //default value, prevent sql injection
    }
    $name = se($_GET, "name", "", false);
    //dynamic query
    $query = "SELECT * FROM Products WHERE stock > 0 and visibility > 0"; //1=1 shortcut to conditionally build AND clauses
    $params = []; //define default params, add keys as needed and pass to execute
    //apply name filter
    if (!empty($name)) {
        $query .= " AND name like :name";
        $params[":name"] = "%$name%";
    }
    //apply column and order sort
    if (!empty($col) && !empty($order)) {
        $query .= " ORDER BY $col $order"; //be sure you trust these values, I validate via the in_array checks above
    }
    $stmt = $db->prepare($query); //dynamically generated query
    //$stmt = $db->prepare("SELECT id, name, description, cost, stock, image FROM BGD_Items WHERE stock > 0 LIMIT 50");
    try {
        $stmt->execute($params); //dynamically populated params to bind
        $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($r) {
            $results = $r;
        }
    } catch (PDOException $e) {
        flash("<pre>" . var_export($e, true) . "</pre>");
    }
    return $results;
}


function redirect($path)
{ //header headache
    //https://www.php.net/manual/en/function.headers-sent.php#90160
    /*headers are sent at the end of script execution otherwise they are sent when the buffer reaches it's limit and emptied */
    if (!headers_sent()) {
        //php redirect
        die(header("Location: " . get_url($path)));
    }
    //javascript redirect
    echo "<script>window.location.href='" . get_url($path) . "';</script>";
    //metadata redirect (runs if javascript is disabled)
    echo "<noscript><meta http-equiv=\"refresh\" content=\"0;url=" . get_url($path) . "\"/></noscript>";
    die();
}
function paginate($query, $params = [], $per_page = 10)
{
    global $page; //will be available after function is called
    try {
        $page = (int)se($_GET, "page", 1, false);
    } catch (Exception $e) {
        //safety for if page is received as not a number
        $page = 1;
    }
    $db = getDB();
    $stmt = $db->prepare($query);
    try {
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("paginate error: " . var_export($e, true));
    }
    $total = 0;
    if (isset($result)) {
        $total = (int)se($result, "total", 0, false);
    }
    global $total_pages; //will be available after function is called
    $total_pages = ceil($total / $per_page);
    global $offset; //will be available after function is called
    $offset = ($page - 1) * $per_page;
}
function persistQueryString($page)
{
    $_GET["page"] = $page;
    return http_build_query($_GET);
}

function filterPurchase()
{
    $results = [];
    $db = getDB();
    //Sort and Filters
    $col = se($_GET, "col", "created", false);
    //allowed list
    if (!in_array($col, ["created", "total_price"])) {
        $col = "total_price"; //default value, prevent sql injection
    }
    $order = se($_GET, "order", "asc", false);
    //allowed list
    if (!in_array($order, ["asc", "desc"])) {
        $order = "asc"; //default value, prevent sql injection
    }
    $name = se($_GET, "name", "", false);
    $userId = get_user_id();
    $base_query = "SELECT * FROM orders ";
    $total_query = "SELECT count(1) as total FROM orders";
    $query = " WHERE `user_id` = $userId";
    $params = [];
    if (!empty($name)) {
        $query .= " AND name like :name";
        $params[":name"] = "%$name%";
    }
    if (!empty($col) && !empty($order)) {
        $query .= " ORDER BY $col $order"; 
    }
    $per_page = 10;
    paginate($total_query . $query, $params, $per_page);
    $page = se($_GET, "page", 1, false); 
    $offset = ($page - 1) * $per_page;
    $query .= " LIMIT :offset, :count";
    $params[":offset"] = $offset;
    $params[":count"] = $per_page;
    //get the records
    $stmt = $db->prepare($base_query . $query);
    foreach ($params as $key => $value) {
        $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindValue($key, $value, $type);
    }
    $params = NULL; 

    try {
        $stmt->execute($params);
        $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($r) {
            $results = $r;
        }
    } catch (PDOException $e) {
        flash("<pre>" . var_export($e, true) . "</pre>");
    }
    return $results;
}

function filterPurchase2()
{
    $results = [];
    $db = getDB();
    //Sort and Filters
    $col = se($_GET, "col", "created", false);
    //allowed list
    if (!in_array($col, ["created", "total_price"])) {
        $col = "total_price"; //default value, prevent sql injection
    }
    $order = se($_GET, "order", "asc", false);
    //allowed list
    if (!in_array($order, ["asc", "desc"])) {
        $order = "asc"; //default value, prevent sql injection
    }
    $name = se($_GET, "name", "", false);
    $userId = get_user_id();
    $base_query = "SELECT * FROM orders";
    $total_query = "SELECT count(1) as total FROM orders";
    $query = " WHERE 1=1";
    $params = [];
    if (!empty($name)) {
        $query .= " AND name like :name";
        $params[":name"] = "%$name%";
    }
    if (!empty($col) && !empty($order)) {
        $query .= " ORDER BY $col $order"; 
    }
    $per_page = 10;
    paginate($total_query . $query, $params, $per_page);
    $page = se($_GET, "page", 1, false); 
    $offset = ($page - 1) * $per_page;
    $query .= " LIMIT :offset, :count";
    $params[":offset"] = $offset;
    $params[":count"] = $per_page;
    //get the records
    $stmt = $db->prepare($base_query . $query);
    foreach ($params as $key => $value) {
        $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindValue($key, $value, $type);
    }
    $params = null; 

    try {
        $stmt->execute($params);
        $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($r) {
            $results = $r;
        }
    } catch (PDOException $e) {
        flash("<pre>" . var_export($e, true) . "</pre>");
    }
    return $results;
}

function paginateReviews(){
    $results = [];
    $db = getDB();
    //Sort and Filters
    $col = se($_GET, "col", "created", false);
    //allowed list
    if (!in_array($col, ["created", "rating"])) {
        $col = "rating"; //default value, prevent sql injection
    }
    $order = se($_GET, "order", "asc", false);
    //allowed list
    if (!in_array($order, ["asc", "desc"])) {
        $order = "asc"; //default value, prevent sql injection
    }
    $item_id = $_GET['id'];
    
    $base_query = "SELECT rating, comment FROM ratings where $item_id = ratings.product_id";
    $total_query = "SELECT rating, comment FROM ratings";
    $params = [];
    if (!empty($col) && !empty($order)) {
        $base_query .= " ORDER BY $col $order"; 
    }
    $per_page = 10;
    paginate($total_query);
    $page = se($_GET, "page", 1, false); 
    $offset = ($page - 1) * $per_page;
    $base_query .= " LIMIT :offset, :count";
    $params[":offset"] = $offset;
    $params[":count"] = $per_page;
    //get the records
    $stmt = $db->prepare($base_query);
    foreach ($params as $key => $value) {
        $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindValue($key, $value, $type);
    }
    $params = null; 

    try {
        $stmt->execute($params);
        $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($r) {
            $results = $r;
        }
    } catch (PDOException $e) {
        flash("<pre>" . var_export($e, true) . "</pre>");
    }
    return $results;
}

?>
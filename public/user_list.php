<?php 
$body_class="user_list"; //aggiunto al body come classe 
$title_page="User List"; //title della pagina
$arr_roles=[1]; //pagina accessibile solo a chi possiede il profilo 1 = admin
?>
<?php ob_start(); ?>
<style>
</style>
<?php $my_style = ob_get_clean(); ?>
<?php
include '../src/header.php';

$id = $_SESSION['id'];
$filters = [
    'id' => isset($_GET['id']) ? $_GET['id'] : '',
    'email' => isset($_GET['email']) ? $_GET['email'] : '',
    'email_verified_at' => isset($_GET['email_verified_at']) ? $_GET['email_verified_at'] : '',
    'roles' => isset($_GET['roles']) ? $_GET['roles'] : [] // Assumiamo che sia un array
];

$sort = [
    'sort_column' => isset($_GET['sort_column']) ? $_GET['sort_column'] : '',
    'sort_direction' => isset($_GET['sort_direction']) ? $_GET['sort_direction'] : 'DESC'
];
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$whereClause = '';
$whereClauses = [];
$params = [];

// Aggiungi filtri
if (!empty($filters['id'])) {
    $whereClauses[] = "u.id = :id";
    $params[':id'] = $filters['id'];
}
if (!empty($filters['email'])) {
    $whereClauses[] = "u.email LIKE :email";
    $params[':email'] = '%' . $filters['email'] . '%';
}
if (!empty($filters['email_verified_at'])) {
    $date = DateTime::createFromFormat('Y-m-d', $filters['email_verified_at']);
    if ($date) {
        $email_verified_at = $date->format('Y-m-d');
        $whereClauses[] = "DATE(email_verified_at) = :email_verified_at";
        $params[':email_verified_at'] = $email_verified_at;
    }
}
if (!empty($filters['roles'])) {
    $placeholders = [];
    foreach ($filters['roles'] as $index => $role) {
        $placeholder = ":roles_$index";
        $placeholders[] = $placeholder;
        $params[$placeholder] = $role;
    }
    $whereClauses[] = "ur.id_role IN (" . implode(', ', $placeholders) . ")";
}
if (!empty($whereClauses)) {
    $whereClause = " WHERE " . implode(" AND ", $whereClauses);
}

// get all decks and the number of card for each deck
$basequery = "SELECT u.*,  GROUP_CONCAT(DISTINCT t_roles.role ORDER BY t_roles.role ASC SEPARATOR '<br />') as role
    , DATE_FORMAT(u.email_verified_at, '%d/%m/%Y - %H:%i') as email_verified_at_format
    , DATE_FORMAT(u.last_login, '%d/%m/%Y - %H:%i') as last_login_format
    , DATE_FORMAT(u.created_at, '%d/%m/%Y - %H:%i') as created_at_format
    , CASE WHEN u.enabled = 0 then 'disabled' else 'enabled' end as enabled_format
    FROM users u
    LEFT JOIN users_roles ur on u.id = ur.id_user
    LEFT JOIN t_roles ON t_roles.id = ur.id_role ";

$groupby = " GROUP BY u.id ";
$query = $basequery . $whereClause . $groupby;

// Aggiungi ordinamento
if (  !empty($sort['sort_column'])  ) {
    $query .= " ORDER BY " . $sort['sort_column'] . " " . $sort['sort_direction'];
}

// Aggiungi paginazione
$offset = ($page - 1) * conf("PAGING_MAXROWS_PERPAGE");
$offset_query = " LIMIT " . conf("PAGING_MAXROWS_PERPAGE") . " OFFSET " . $offset;


// Esegui la query per ottenere i dati
$result = getRowsQuery($query . $offset_query, $params);
//dd($result);

// Costruisci la query per contare il totale dei record
$totalRecords = getValueQuery("SELECT COUNT(*) as total FROM ($query) as t", $params, "total");
$totalPages = ceil(intval($totalRecords) / conf("PAGING_MAXROWS_PERPAGE"));

// Calcola gli indici del primo e dell'ultimo record visualizzati
list($firstRecordIndex, $lastRecordIndex) = calculateRecordIndices($page, conf("PAGING_MAXROWS_PERPAGE"), $totalRecords);
?>

<h2>Your Decks</h2>
<?php print_flash("msg"); ?>
<?php //print_errors($error); ?>

<br />
<a class="btn black inline-block" href="user_card.php?op=i&idcrypt=<?php echo rawurlencode(encrypt("-1")); ?>">New User</a>
<br />

<!-- Form per i filtri -->
<form method="get" id="filterForm" class="formFields">
    <div class="form-row">
        <div class="form-group">
            <label for="id">ID</label>
            <input type="text" id="id" name="id" value="<?php echo pid_htmlspecialchars($filters['id']); ?>">
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="text" id="email" name="email" value="<?php echo pid_htmlspecialchars($filters['email']); ?>">
        </div>
        <div class="form-group">
            <label for="email_verified_at">Data</label>
            <input type="date" id="email_verified_at" name="email_verified_at" value="<?php echo pid_htmlspecialchars($filters['email_verified_at']); ?>">
        </div>
        <div class="form-group">
            <label for="roles">Roles</label>
            <select multiple id="roles" name="roles[]" size="2">
                <?php $rows_roles = getRowsQuery("select * from t_roles order by id asc", []); ?>
                <?php foreach($rows_roles as $r): ?>
                    <option value="<?php echo $r["id"] ?>" <?php echo in_array($r["id"], $filters['roles']) ? 'selected' : ''; ?>><?php echo $r["role"] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <input type="hidden" name="sort_column" id="sort_column" value="<?php echo pid_htmlspecialchars($sort['sort_column'] ?? ""); ?>">
    <input type="hidden" name="sort_direction" id="sort_direction" value="<?php echo pid_htmlspecialchars($sort['sort_direction'] ?? ""); ?>">
    <input type="hidden" name="page" id="page" value="<?php echo $page; ?>">
    <button type="submit" class="btn black inline-block">Filter</button>
    <button type="button" id="btnClearForm" class="btn black inline-block">Clear</button>
</form>

<div>
    Record totali: <?php echo $totalRecords; ?> - Stai visualizzando i risultati da <?php echo $firstRecordIndex; ?> a <?php echo $lastRecordIndex; ?>
</div>
<!-- Paginazione -->
<?php echo generatePaginationLinks($totalPages, $page); ?>

<div style="overflow-x:auto;">  <!-- Quick fix - Responsive table -->
    <table class="tableresults">
        <tr>
            <th style="width:50px;"></th>
            <th style="width:40px;"><a href="#" class="sort-link" data-column="id">ID</a></th>
            <th style="width:90px;"><a href="#" class="sort-link" data-column="email">Email</a></th>
            <th style="width:90px;"><a href="#" class="sort-link" data-column="name">Name</a></th>
            <th style="width:70px;"><a href="#" class="sort-link" data-column="enabled">Enabled</a></th>
            <th style="width:70px;">Role</th>
        </tr>
        <?php foreach ($result as $row) { ?>
            <tr>
                <td class="nowrap">
                    <a class="aUpUser" href="user_card.php?op=u&idcrypt=<?php echo rawurlencode(encrypt($row["id"])); ?>"><i class="bi bi-pen"></i> Edit</a>
                    <br />
                    <a class="aDelUser" href="user_card.php?op=d&idcrypt=<?php echo rawurlencode(encrypt($row["id"])); ?>"><i class="bi bi-trash"></i> Delete</a>
                </td>
                <td class="text-center"><?php echo $row['id']; ?></td>
                <td><?php echo pid_htmlspecialchars($row['email']); ?></td>
                <td><?php echo pid_htmlspecialchars($row['name']); ?></td>
                <td class="text-center"><?php echo pid_htmlspecialchars($row['enabled_format']); ?></td>
                <td><?php echo $row['role']; ?></td>
            </tr>
        <?php } ?>
    </table>
</div>

<?php ob_start(); ?>
<script>
    document.addEventListener("DOMContentLoaded", function(){
        // click per gestire cambio pagina
        addEventListenerToAll("a[data-page]", "click", function(event){
            event.preventDefault(); // block default action
            const page = this.getAttribute('data-page');
            getById('page').value = page;
            getById('filterForm').submit();
        });

        // click per gestire ordinamento risultati
        addEventListenerToAll("a.sort-link", "click", function(event){
            event.preventDefault(); // block default action
            const column = this.getAttribute('data-column');
            const currentSortColumn = getById('sort_column').value;
            const currentSortDirection = getById('sort_direction').value;

            let newSortDirection = 'DESC';
            if (currentSortColumn === column && currentSortDirection === 'DESC') {
                newSortDirection = 'ASC';
            }

            getById('sort_column').value = column;
            getById('sort_direction').value = newSortDirection;
            getById('page').value = 1; // Reset pagina alla prima
            getById('filterForm').submit();
        });
        
        // click per pulire la form dei filtri
        getById("btnClearForm").addEventListener("click", function() {
            getById("id").value = "";
            getById("email").value = "";
            getById("email_verified_at").value = "";
            let roles = getById("roles");
            for (i=0;i<roles.options.length;i++){
                roles.options[i].selected=false;
            }
        });
        
        addEventListenerToAll("a.aUpUser", "click", function(event){
            event.preventDefault(); // block default action
            let url = this.getAttribute("href");
            let currentUrl = window.location.href;
            window.location.href = url + "&back=" + encodeURIComponent(currentUrl);
        });

        addEventListenerToAll("a.aDelUser", "click", function(event){
            event.preventDefault(); // block default action
            if (confirm("Confermare cancellazione utente") == true) {
                let url = this.getAttribute("href");
                let currentUrl = window.location.href;
                window.location.href = url + "&back=" + encodeURIComponent(currentUrl);
            } 
        });
        
    });
</script>
<?php 
$my_script = ob_get_clean();
include '../src/footer.php';

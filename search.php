<?php
// search.php
session_start();
require_once 'dbconnect.php'; // uses your existing MySQL connection

// 1) Read filters safely
$q      = isset($_GET['q']) ? trim($_GET['q']) : '';
$region = isset($_GET['region']) ? trim($_GET['region']) : '';
$tag    = isset($_GET['tag']) ? trim($_GET['tag']) : '';

// 2) Build SQL dynamically + prepared params (optimized: GROUP_CONCAT tags)
$sql = "
  SELECT 
    r.RecipeID,
    r.Name,
    r.Description,
    r.Region,
    r.CuisineType,
    COALESCE(AVG(rv.Rating), 0) AS AvgRating,
    GROUP_CONCAT(DISTINCT t.TagName ORDER BY t.TagName SEPARATOR ',') AS Tags
  FROM recipes r
  LEFT JOIN recipetags t ON t.RecipeID = r.RecipeID
  LEFT JOIN reviews rv   ON rv.RecipeID = r.RecipeID
  WHERE 1=1
";

$types = '';
$params = [];

// Name/keyword: match against recipe name or description
if ($q !== '') {
  $sql .= " AND (r.Name LIKE CONCAT('%', ?, '%') OR r.Description LIKE CONCAT('%', ?, '%'))";
  $types .= 'ss';
  $params[] = $q;
  $params[] = $q;
}

// Exact region match (switch to LIKE if you prefer partial)
if ($region !== '') {
  $sql .= " AND r.Region = ?";
  $types .= 's';
  $params[] = $region;
}

// Exact tag match
if ($tag !== '') {
  $sql .= " AND t.TagName = ?";
  $types .= 's';
  $params[] = $tag;
}

$sql .= "
  GROUP BY r.RecipeID, r.Name, r.Description, r.Region, r.CuisineType
  ORDER BY AvgRating DESC, r.Name ASC
";

// 3) Prepare & execute
$stmt = $conn->prepare($sql);
if (!$stmt) {
  die('Prepare failed: ' . htmlspecialchars($conn->error));
}
if (!empty($params)) {
  $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// 4) Distinct Regions and Tags for dropdowns
$regions = [];
$tags = [];

$regRes = $conn->query("SELECT DISTINCT Region FROM recipes WHERE Region IS NOT NULL AND Region <> '' ORDER BY Region ASC");
if ($regRes) {
  while ($r = $regRes->fetch_assoc()) $regions[] = $r['Region'];
}

$tagRes = $conn->query("SELECT DISTINCT TagName FROM recipetags WHERE TagName IS NOT NULL AND TagName <> '' ORDER BY TagName ASC");
if ($tagRes) {
  while ($trow = $tagRes->fetch_assoc()) $tags[] = $trow['TagName'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Search Recipes</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <!-- Match your site’s typography -->
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

  <style>
    :root{
      --brand1:#ffcc00;
      --brand2:#ff9900;
      --ink:#333;
      --muted:#666;
      --card:#ffffff;
      --pill-bg:#fff3cd;
      --pill-text:#7a5d00;
      --ok:#2e7d32;
      --border:#eee;
      --shadow:0 10px 24px rgba(0,0,0,.12);
    }
    *{box-sizing:border-box}
    body{
      margin:0; font-family:'Roboto', sans-serif; color:var(--ink);
      background: linear-gradient(120deg, var(--brand1), var(--brand2));
      background-attachment: fixed;
      min-height:100vh;
    }
    /* Header bar to match homepage vibe */
    .topbar{
      background: linear-gradient(120deg, var(--brand1), var(--brand2));
      color:#000; padding:14px 20px; display:flex; align-items:center; justify-content:space-between;
      position:sticky; top:0; z-index:5; box-shadow:0 4px 8px rgba(0,0,0,.15);
    }
    .logo{font-weight:700; font-size:18px;}
    .topbar a{ color:#000; text-decoration:none; background:rgba(255,255,255,.35); padding:8px 12px; border-radius:8px; }
    .topbar a:hover{ background:rgba(255,255,255,.55); }

    /* Card container */
    .page{
      max-width:1100px; margin:26px auto; padding:0 16px;
    }
    .card{
      background:var(--card); border-radius:14px; box-shadow:var(--shadow); border:1px solid var(--border);
    }

    /* Search panel */
    .search-wrap{ padding:18px; }
    .search-title{ margin:0 0 10px; font-size:22px; color:#000; }
    .grid{ display:grid; grid-template-columns: 1.2fr 0.8fr 0.8fr auto; gap:10px; }
    .grid input[type=text], .grid select{
      width:100%; padding:10px 12px; border:1px solid #ddd; border-radius:10px; font-size:14px;
      background:#fff;
    }
    .btn{
      padding:10px 16px; border:none; border-radius:10px; cursor:pointer; font-weight:600;
      background:#333; color:#fff; transition:.2s; white-space:nowrap;
    }
    .btn:hover{ background:#444; }
    .btn-light{
      background:#fff; color:#333; border:1px solid #ddd;
    }
    .btn-light:hover{ background:#f8f8f8; }
    .filters-row{ display:flex; align-items:center; gap:8px; margin-top:10px; flex-wrap:wrap; }
    .chip{
      background:#fff; border:1px solid #eee; border-radius:999px; padding:6px 10px; font-size:12px; color:var(--muted);
    }

    /* Results */
    .section{ margin-top:18px; }
    .section h2{ margin:0 0 10px; color:#000; }
    .table-wrap{ overflow:auto; border-radius:14px; box-shadow:var(--shadow); }
    table{ width:100%; border-collapse:collapse; background:#fff; }
    thead th{
      position:sticky; top:0; z-index:1; text-align:left; padding:12px; background:#222; color:#fff; font-weight:600;
    }
    tbody td{ padding:12px; border-top:1px solid #f0f0f0; vertical-align:top; }
    tbody tr:nth-child(even){ background:#fafafa; }
    tbody tr:hover{ background:#fff8e1; }
    .muted{ color:var(--muted); font-size:13px; }
    .pills{ display:flex; gap:6px; flex-wrap:wrap; }
    .pill{ background:var(--pill-bg); color:var(--pill-text); border-radius:999px; padding:4px 8px; font-size:12px; }

    /* Responsive */
    @media (max-width: 900px){
      .grid{ grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

  <div class="topbar">
    <div class="logo">Cultural Cuisine Explorer</div>
    <div><a href="homepage.php">← Back to Homepage</a></div>
  </div>

  <div class="page">
    <div class="card search-wrap">
      <h1 class="search-title">Search Recipes</h1>
      <form method="get" action="search.php">
        <div class="grid">
          <input type="text" name="q" placeholder="Search by name or description…" value="<?php echo htmlspecialchars($q); ?>" />
          <select name="region">
            <option value="">All Regions</option>
            <?php foreach ($regions as $r): ?>
              <option value="<?php echo htmlspecialchars($r); ?>" <?php echo $region===$r ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars(ucfirst($r)); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <select name="tag">
            <option value="">All Tags</option>
            <?php foreach ($tags as $tg): ?>
              <option value="<?php echo htmlspecialchars($tg); ?>" <?php echo $tag===$tg ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($tg); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div style="display:flex; gap:8px;">
            <button class="btn" type="submit">Search</button>
            <a class="btn btn-light" href="search.php">Reset</a>
          </div>
        </div>

        <!-- Active filter chips -->
        <div class="filters-row">
          <?php if ($q !== ''): ?><span class="chip">Query: “<?php echo htmlspecialchars($q); ?>”</span><?php endif; ?>
          <?php if ($region !== ''): ?><span class="chip">Region: <?php echo htmlspecialchars(ucfirst($region)); ?></span><?php endif; ?>
          <?php if ($tag !== ''): ?><span class="chip">Tag: <?php echo htmlspecialchars($tag); ?></span><?php endif; ?>
        </div>
      </form>
    </div>

    <div class="section">
      <h2>Results</h2>
      <div class="table-wrap card">
        <table>
          <thead>
            <tr>
              <th>Recipe</th>
              <th>Region</th>
              <th>Cuisine Type</th>
              <th>Average Rating</th>
              <th>Tags</th>
            </tr>
          </thead>
          <tbody>
          <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
              <?php
                $tagList = [];
                if (!empty($row['Tags'])) {
                  // explode the aggregated tags
                  $tagList = array_filter(array_map('trim', explode(',', $row['Tags'])));
                }
              ?>
              <tr>
                <td>
                  <strong><?php echo htmlspecialchars($row['Name']); ?></strong><br>
                  <span class="muted"><?php echo nl2br(htmlspecialchars($row['Description'])); ?></span>
                </td>
                <td><?php echo htmlspecialchars($row['Region']); ?></td>
                <td><?php echo htmlspecialchars($row['CuisineType']); ?></td>
                <td><?php echo number_format((float)$row['AvgRating'], 1); ?>/5</td>
                <td>
                  <div class="pills">
                    <?php foreach ($tagList as $tg): ?>
                      <span class="pill"><?php echo htmlspecialchars($tg); ?></span>
                    <?php endforeach; ?>
                  </div>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="5">No recipes found. Try different filters.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</body>
</html>

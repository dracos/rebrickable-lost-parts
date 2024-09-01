<? $title = 'Rebrickable Lost Parts bulk entry'; require 'site.inc'; ?>
<?php

$config = parse_ini_file('config.ini');
$api = new Rebrickable($config['api_key']);

$set = isset($_REQUEST['set']) ? $_REQUEST['set'] : '';

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
main img { max-width: 4em; }
main input[type=checkbox] { transform:scale(2, 2); }
main input[type=number] { width: 4em; }
main span { display: inline-block; width: 1em; height: 1em; }
tr:has(input:checked),
tr:not(:has(input:placeholder-shown)):nth-child(n+2) { opacity: 0.3; }
td:nth-child(2), td:last-child { text-align: left; }
th:nth-child(1), th:last-child { text-align: left; }
th, td { text-align: center; padding: 0 0.5em; }
ul.results { padding: 1em 2em; border: solid 2px #f66; }
</style>
</head>
<body>
<main>
<?php

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    print '<ul class="results">';
    if ($_POST['username'] && $_POST['password']) {
        $success = $api->get_token($_POST['username'], $_POST['password']);
        if ($success) {
            $result = $api->add_lost_parts($_POST['lost']);
            foreach ($result as $r) {
                print "<li>Marked $r->lost_quantity of $r->inv_part_id as lost";
            }
            print '<li><a href="https://rebrickable.com/my/lostparts/">See your lost parts</a>';
        } else {
            print '<li>Incorrect username or password';
        }
    } else {
        print '<li>Please provide a username and password';
    }
    print '</ul>';
}

?>

<p>Say you’ve been donated a box of Lego, and some manuals, which sort of make up some sets but probably none complete.
Before donating them onwards, you’d like to sort them out and complete them if possible, perhaps buying any missing parts.
To do this on <a href="https://rebrickable.com/">Rebrickable</a>, it appears you have to add the set to your collection, then go through and mark every missing
part one by one, taking a good number of clicks for each part (click on part, click on Lost Parts, enter number, click save, close part, repeat). So I made this,
which will add multiple things to your Lost Parts at once, remember where you’ve got to if you refresh, and make the process a lot smoother.
Make sure you’ve added the set to a list first, and then:</p>

<form>
    <p><label>Set ID:
    <input type="text" name="set" value="<?=htmlspecialchars($set) ?>">
    </label>
    <p><input type="submit" value="Look up set">
</form>

<?php

if ($set) {
    if (!preg_match('#-#', $set)) {
        $set .= '-1';
    }
    $set_details = $api->get("sets/$set/", []);
    $parts = $api->get("sets/$set/parts", ["inc_color_details" => 0 ]);
?>

<h2><?=$set_details->name?></h2>
<a href="<?=$set_details->set_url?>"><img style="max-width:8em" src="<?=$set_details->set_img_url?>" alt=""></a>

<form method="post" id="form_missing">
<table>
<tr><th colspan="2">Part</th><th>Quantity</th><th>Got&nbsp;all?</th><th>Num missing</th></tr>
<?php
foreach ($parts as $result) {
    if ($result->is_spare) { # Don't care at present
        continue;
    }
?>
<tr>
    <td>
        <img loading=lazy src="<?=$result->part->part_img_url?>" alt="">
    </td><td>
        <?=$result->part->name?>
        <br>
        <?=$result->color->name?>
        <span style="background-color: <?=$result->color->rgb?>"></span>
        <!-- $result->color->is_trans; -->
    </td><td>
        <?=$result->quantity?>
    </td><td>
        <input type="checkbox" name="all[<?=$result->inv_part_id?>]">
    </td><td style="white-space: nowrap">
        <input type="number" name="lost[<?=$result->inv_part_id?>]" value="" placeholder="" inputmode="numeric" pattern="[0-9]*" min=0>
        <button>1</button>
        <?php if ($result->quantity > 1) { ?><button>All</button><?php } ?>
    </td>
</tr>
<?php } ?>
</table>

<p>To authenticate this request with your Rebrickable account, you need to
supply your Rebrickable username and password, they do not provide another way
to authenticate, such as OAuth. I do not store this data in any way, but it is
up to you &ndash; you can <a href="https://github.com/dracos/rebrickable-lost-parts">install and run</a> the code yourself if you have any concerns.

<p><label>Rebrickable username: <input type="text" name="username" value=""></label>
<p><label>Rebrickable password: <input type="password" name="password" value=""></label>

<p><input style="font-size: 200%" type="submit" value="Submit">
</form>

<?php } ?>

</main>

<script>

function load_state() {
    var set = document.querySelector('input[name=set]').defaultValue;
    var data = localStorage.getItem('state-' + set);
    if (!data) return;
    var data = new URLSearchParams(data);
    data.delete('password');
    for (var [key, value] of data) {
        var elt = form_missing.querySelector('input[name="' + key + '"]');
        if (elt) {
            if (value === 'on') {
                elt.checked = true;
            } else {
                elt.value = value;
            }
        }
    }
}
function save_state() {
    var data = new URLSearchParams(new FormData(form_missing));
    data.delete('password');
    data = data.toString();
    var set = document.querySelector('input[name=set]').defaultValue;
    localStorage.setItem('state-' + set, data);
}
load_state();

// Checkbox clicked
form_missing.addEventListener('change', function(e) {
    save_state();
});

// Button clicked
form_missing.addEventListener('click', function(e) {
    if (e.target.nodeName === 'BUTTON') {
        e.preventDefault();
        if (e.target.textContent == 1) {
            e.target.parentNode.querySelector('input').value = e.target.textContent;
        } else {
            e.target.parentNode.querySelector('input').value = e.target.parentNode.previousElementSibling.previousElementSibling.textContent.trim();
        }
        save_state();
    }
});
</script>
</body>
</html>
<?php

class Rebrickable {
    private $token;
    private $api_key;

    public function __construct($api_key) {
        $this->api_key = $api_key;
    }

    public function get($url, $params) {
        $params['key'] = $this->api_key;
        $next = "https://rebrickable.com/api/v3/lego/$url?" . http_build_query($params);
        $out = [];
        while ($next) {
            $data = json_decode(file_get_contents($next));
            if (property_exists($data, 'results')) {
                $out = array_merge($out, $data->results);
                $next = $data->next;
            } else {
                $out = $data;
                $next = '';
            }
        }
        return $out;
    }

    public function get_token($username, $password) {
        $url = "https://rebrickable.com/api/v3/users/_token/?key=" . $this->api_key;
        $data = http_build_query([ 'username' => $username, 'password' => $password ]);
        $context = stream_context_create(array('http' => array(
            'method'  => 'POST',
            'header'  => 'Content-Type: application/x-www-form-urlencoded',
            'content' => $data,
        )));
        $result = file_get_contents($url, false, $context);
        $result = json_decode($result);

        if (property_exists($result, 'user_token')) {
            $this->token = $result->user_token;
            return true;
        } else {
            return false;
        }
    }

    private function post_api($url, $data) {
        $url = "https://rebrickable.com/api/v3/users/" . $this->token . "/$url?key=" . $this->api_key;
        $data = json_encode($data);
        $context = stream_context_create(array('http' => array(
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\nContent-Length: " . strlen($data),
            'content' => $data,
        )));
        $result = file_get_contents($url, false, $context);
        $result = json_decode($result);
        return $result;
    }

    public function add_lost_parts($parts) {
        $data = [];
        foreach ($parts as $id => $quantity) {
            if ($quantity) {
                $data[] = ["inv_part_id" => $id, "lost_quantity" => $quantity];
            }
        }
        $result = $this->post_api('lost_parts/', $data);
        return $result;
    }
}

footer();

<?php

/* BASECLASS*/
class CContent {

    public $db;
    protected $isAdmin    = null;
    protected $id         = null; 
    protected $title      = null; 
    protected $slug       = null; 
    protected $url        = null; 
    protected $data       = null; 
    protected $type       = null; 
    protected $filter     = null; 
    protected $published  = null; 

    protected $save       = null; 
    protected $remove     = null; 
    protected $noRemove   = null; 
    public $user;
    public $categories = null;


    public function __construct($database) {
        
        $this->db =         $database;
        // Get parameters 
        $this->isAdmin =    isset($_SESSION['user']) ? $_SESSION['user'] : null;
        $this->id     =     isset($_POST['id'])    ? strip_tags($_POST['id']) : (isset($_GET['id']) ? strip_tags($_GET['id']) : null);
        $this->title =      isset($_POST['title']) ? $this->slugify($_POST['title']) : null;
        $this->slug =       isset($_POST['slug']) ? $_POST['slug'] : null;
        $this->url =        isset($_POST['url']) ? strip_tags($_POST['url']) : null;
        $this->data =       isset($_POST['data']) ? $_POST['data'] : array();
        $this->type =       isset($_POST['type']) ? strip_tags($_POST['type']) : array();
        $this->filter =     isset($_POST['filter']) ? $_POST['filter'] : array();
        $this->published =  isset($_POST['published']) ? strip_tags($_POST['published']) : array();
        #$this->categories =       isset($_POST['categories']) ? is_numeric($_POST['categories']) : array();
        $this->categories   =     isset($_POST['categories'])  ? $_POST['categories'] : array();


        $this->save =       isset($_POST['save']) ? true : false;
        $this->remove =     isset($_POST['remove']) ? true : false; 
        $this->noRemove =     isset($_POST['noRemove']) ? true : false; 


        #$this->acronym =    isset($_SESSION['user']) ? $_SESSION['user']->acronym : null;
    }

    public function getUser(){
        $user = $this->isAdmin;
        return $user;
    }

    public function renderAvailableContent() {
        
        $sql = 'SELECT *, (published <= NOW()) AS available FROM Content;';
        $res = $this->db->ExecuteSelectQueryAndFetchAll($sql);


        $items = null;
        if ($this->isAdmin) {
            foreach ($res AS $key => $val) {
                $items .= "<li>{$val->type} (" 
                .(!$val->available ? 'inte ' : null) . "publicerad): " 
                        . htmlentities($val->title, null, 'UTF-8') 
                        . " (<a href='removeController.php?id={$val->id}'>ta bort</a> <a href='editController.php?id={$val->id}'>editera</a> <a href='" 
                        . $this->getUrlToContent($val) . "'>visa</a>)</li>\n";
            }
        }
        else {
            foreach ($res AS $key => $val) {
                $items .= "<li>{$val->type} (" 
                . (!$val->available ? 'inte ' : null) . "publicerad): " 
                        . htmlentities($val->title, null, 'UTF-8') 
                        . " (<a href='" . $this->getUrlToContent($val) . "'>visa</a>)</li>\n";
            }
        }
        return $items;
    }


    
    
    public function updateContent() {

        $output = null;
        if ($this->save) {
            $sql = '
                UPDATE Content SET
                  title   = ?,
                  slug    = ?,
                  url     = ?,
                  DATA    = ?,
                  TYPE    = ?,
                  FILTER  = ?,
                  published = ?,
                  updated = NOW()
                WHERE 
                  id = ?
              ';
            $url = empty($url) ? null : $url;
            $params = array($this->title, $this->slug, $this->url, $this->data, $this->type, $this->filter, $this->published, $this->id);
            $res = $this->db->ExecuteQuery($sql, $params);
            if ($res) {
                #$output = 'Informationen sparades.';
                header('Location: blog.php');
            } else {
                #$output = 'Informationen sparades EJ.<br><pre>' . print_r($db->Dump(), 1) . '</pre>';
                $output = 'Nått blev fel!';
            }
        }
        else if($this->remove) {
            $this->deleteContentById($this->id);
        }
        else if($this->noRemove) {
                 #header('Location: viewController.php');
        }
        return $output; 
    }



    
    public function insertContent() {
         $output = null;
         $res = array();

        if ($this->save) {
            $sql = 'INSERT INTO Content(
                title, 
                slug, 
                url, 
                DATA, 
                TYPE, 
                FILTER, 
                published, 
                updated,
                views,
                user) 
                VALUES(?, ?, ?, ?, ?, ?, NOW(), NOW(),0,?)'; 
            
            $url = empty($url) ? null : $url;
            $params = array($this->title, $this->slug, $this->url, $this->data, $this->type, $this->filter, $this->isAdmin);
            $res = $this->db->ExecuteQuery($sql, $params);
            if ($res) {
                // Fetch the id for the request, needed when updating
                // movie 2 genre table
                $id = $this->db->LastInsertId();
                #$getIp = CTracking::getUserIP($this->db);
                #$id = CDatabase::LastInsertId();
                // Insert genre into the movie 2 genre mapping table
                foreach($this->categories as $genre)  
                {
                  #$genreId = $this->getGenreId($genre);
                  $sql = "INSERT INTO Content2Categories (idContent, idCategories) VALUES (?, ?);";
                  $this->db->executeSelectQueryAndFetchAll($sql, array($id, $genre));
                } 
                #header('Location: blog.php');
            } else {
                $output = 'Informationen EJ tillagd.<br><pre>' . print_r($this->db->Dump(), 1) . '</pre>';
            }
        }
        return $output; 
    }




    public function renderEditForm($output) {
  
        $content = $this->selectContentById($this->id); 
        $this->sanitizeVariables($content); 
        
        $html = null; 
        $html .= "<form method='POST' enctype='multipart/form-data' >";
        $html .="<label class='checkbox-inline'><input type='checkbox' value=''>C#</label> <label class='checkbox-inline'>  <input type='checkbox' value=''>MySQL</label> <label class='checkbox-inline'><input type='checkbox' value=''>MVC</label>";
        $html .="<label class='checkbox-inline'><input type='checkbox' value=''>Android</label> <label class='checkbox-inline'><input type='checkbox' value=''>PHP</label> <label class='checkbox-inline'><input type='checkbox' value=''>Bootstrap</label>";

       # $html .= "<input type='hidden' name='id' value='{$this->id}'/>";

        $html .= "<div class='form-group float-label-control'>
                        <label for=''>Title</label>
                        <input type='text' class='form-control' name='title' value='{$this->title}' placeholder='Title'>
                    </div>";

        $html .= "<div class='form-group float-label-control'>
                        <label for=''>Slug</label>
                        <input type='text' class='form-control' name='slug' value='{$this->slug}' placeholder='Slug'>
                    </div>";

        $html .= "<div class='form-group float-label-control'>
                        <label for=''>Url</label>
                        <input type='text' class='form-control' name='url' value='{$this->url}' placeholder='Url'>
                    </div>";

        $html .= "<div class='form-group float-label-control'>
                        <label for=''>Textarea</label>
                        <textarea class='form-control' placeholder='data' name='data' rows='10' style='width:100%'>{$this->data}</textarea>
                    </div>";

        $html .= "<div class='form-group float-label-control'>
                        <label for=''>Type</label>
                        <input type='text' class='form-control' name='type' value='{$this->type}' placeholder='post'>
                    </div>";

        $html .= "<div class='form-group float-label-control'>
                        <label for=''>Filter</label>
                        <input type='text' class='form-control' name='filter' value='bbcode, nl2br, link' placeholder='Filter'>
                    </div>";
        $html .= "<p class=buttons><input type='submit' name='save' value='Spara'/></p>";
        $html .= "<p><a href='blog.php'>Visa alla</a></p>";
        $html .= "<output>{$output}</output>";
        $html .= "</form>";
        return $html; 
    }

    public function renderInsertForm($output) {
        $html = null;
        $html .= "<form method='POST' enctype='multipart/form-data' >";
       # $html .= "<input type='hidden' name='id' value='{$this->id}'/>";

        $html .= "<div class='form-group float-label-control'>
                        <label for=''>Title</label>
                        <input type='text' class='form-control' name='title' placeholder='Title'>
                    </div>";

        $html .= "<div class='form-group float-label-control'>
                        <label for=''>Slug</label>
                        <input type='text' class='form-control' name='slug' placeholder='Slug'>
                    </div>";

        $html .= "<div class='form-group float-label-control'>
                        <label for=''>Url</label>
                        <input type='text' class='form-control' name='url' placeholder='Url'>
                    </div>";

        $html .= "<div class='form-group float-label-control'>
                        <label for=''>Textarea</label>
                        <textarea class='form-control' placeholder='data' name='data' rows='10' style='width:100%'></textarea>
                    </div>";

        $html .= "<div class='form-group float-label-control'>
                        <label for=''>Type</label>
                        <input type='text' class='form-control' name='type' value='post' placeholder='post'>
                    </div>";

        $html .= "<div class='form-group float-label-control'>
                        <label for=''>Filter</label>
                        <input type='text' class='form-control' name='filter' value='bbcode, nl2br, link' placeholder='Filter'>
                    </div>";
    $html .="<div class='container text-center'>";
$html .="<label class='checkbox-inline'><input type='checkbox' name='categories[]' value='1'>Bootstrap</label>  <label class='checkbox-inline'><input type='checkbox' name='categories[]' value='4'>MySQL</label>               <label class='checkbox-inline'><input type='checkbox' name='categories[]' value='7'>MVC</label>";
$html .="<label class='checkbox-inline'><input type='checkbox' name='categories[]' value='2'>C#</label>         <label class='checkbox-inline'><input type='checkbox' name='categories[]' value='5'>Android</label>             <label class='checkbox-inline'><input type='checkbox' name='categories[]' value='8'>OOP</label>";
$html .="<label class='checkbox-inline'><input type='checkbox' name='categories[]' value='3'>PHP</label>        <label class='checkbox-inline'><input type='checkbox' name='categories[]' value='6'>Gamification</label>        <label class='checkbox-inline'><input type='checkbox' name='categories[]' value='9'>Other</label>";
$html .="<label class='checkbox-inline'><input type='checkbox' name='categories[]' value='10'>News</label>      <label class='checkbox-inline'><input type='checkbox' name='categories[]' value='11'>IT</label>                 <label class='checkbox-inline'><input type='checkbox' name='categories[]' value='12'>AJAX</label>";
$html .="<label class='checkbox-inline'><input type='checkbox' name='categories[]' value='13'>JavaScript</label>        <label class='checkbox-inline'><input type='checkbox' name='categories[]' value='14'>HTML</label>        <label class='checkbox-inline'><input type='checkbox' name='categories[]' value='15'>CSS</label>";
$html .="<label class='checkbox-inline'><input type='checkbox' name='categories[]' value='16'>BootSnipp</label>        <label class='checkbox-inline'><input type='checkbox' name='categories[]' value='17'>Tutorials</label>        <label class='checkbox-inline'><input type='checkbox' name='categories[]' value='18'>Worth Remembering</label>";
$html .="<label class='checkbox-inline'><input type='checkbox' name='categories[]' value='19'>Studies</label>        <label class='checkbox-inline'><input type='checkbox' name='categories[]' value='20'>Science</label>        <label class='checkbox-inline'><input type='checkbox' name='categories[]' value='21'>DATA</label>";
$html .="<hr>";

$html .= CBlog::categories();


$html .="</div>";

        $html .= "<p class=buttons><input type='submit' name='save' value='Skapa'/></p>";
        $html .= "<p><a href='blog.php'>Visa alla</a></p>";
        $html .= "<output>{$output}</output>";

        $html .= "</form>";
        return $html;
    }

    
    public function renderRemoveForm($output) {
        $content = $this->selectContentById($this->id); 
        $this->sanitizeVariables($content); 
        
        $html = null; 
        $html .= "<form method=post>";
        $html .= "<fieldset>";
        $html .= "<legend>Ta bort innehåll</legend>";
        $html .= "<input type='hidden' name='id' value='{$this->id}'/>";
        $html .= "Vill du verkligen ta bort inlägget med titeln: {$this->title}</label></p>";
        $html .= "<p class=buttons><input type='submit' name='remove' value='Ja'/>";
        $html .= " <input type='submit' name='noRemove' value='Nej'/></p>";
        $html .= "<p><a href='viewController.php'>Visa alla</a></p>";
        $html .= "<output>{$output}</output>";
        $html .= "</fieldset>";
        $html .= "</form>";
        return $html; 
    }

    public function resetDB() {

        $sql = file_get_contents('resetBlogTables.sql');
        $res = $this->db->ExecuteQuery($sql);

        if ($res) {
            return true;
        } else {
            return false;
        }
    }
    
    public function sanitizeVariables($c) {
        
        $this->title      = htmlentities($c->title, null, 'UTF-8');
        $this->slug       = htmlentities($c->slug, null, 'UTF-8');
        $this->url        = htmlentities($c->url, null, 'UTF-8');
        $this->data       = htmlentities($c->DATA, null, 'UTF-8');
        $this->type       = htmlentities($c->TYPE, null, 'UTF-8');
        $this->filter     = htmlentities($c->FILTER, null, 'UTF-8');
        $this->published  = htmlentities($c->published, null, 'UTF-8');

    }
    
    private function selectContentById($id) {
        
        $sql = 'SELECT * FROM Content WHERE id = ?';
        $res = $this->db->ExecuteSelectQueryAndFetchAll($sql, array($id));

        if (isset($res[0])) {
            $c = $res[0];
        } else {
            die('Misslyckades: det finns inget innehåll med sådant id.');
        }
        return $c;
    }
    
    private function deleteContentById($id) {
        
        $sql = "DELETE FROM Content WHERE id = ?;";
        $res = $this->db->ExecuteQuery($sql, array($id));
        
        if($res){
            header("Location: viewController.php");
        }else{
            $output = "Informationen raderades EJ.<br><pre>" .print_r($this->db->Dump(), 1) ."</pre>";
        } 
        return $output;
    }

    private function getUrlToContent($content) {
        switch ($content->type) {
            case 'page': return "pageController.php?url={$content->url}";
                break;
            case 'post': return "blogController.php?slug={$content->slug}";
                break;
            default: return null;
                break;
        }
    }
    
   /**
   * Create a slug of a string, to be used as url.
    *
   * @param string $str the string to format as slug.
   * @returns str the formatted slug. 
   */
  private function slugify($str) {
        $str = mb_strtolower(trim($str));
        $str = str_replace(array('å', 'ä', 'ö'), array('a', 'a', 'o'), $str);
        $str = preg_replace('/[^a-z0-9-]/', '-', $str);
        $str = trim(preg_replace('/-+/', '-', $str), '-');
        return $str;
    }


      /************************************************************************** 
  * getGenreId, fetches the unique id for a genre. The id should be used in
  * the movie 2 genre mapping table
  * @param $sql, the SQL command
  * @param $useFilter, filtering on or off (true, false)
  * @return the row with matching type
  */

  private function getGenreId($name){
    $sql = "SELECT * from Categories WHERE name = ?;";
    $res = $this->db->executeSelectQueryAndFetchAll($sql, array($name));
    return $res[0]->id;  
  } 

  /************************************************************************** 
     * fetchAndFilter, fetches data from database and performs filtering of
     * of the data field. The filters to use is also stored in database.
     * It is possible to turn the text filtering off.
     * @param $sql, the SQL command
     * @param $useFilter, filtering on or off (true, false)
     * @return the row with matching type
     */

     private function fetchAndFilter($sql, $params, $useFilter)
     {
         // Fetch from database
         $result = $this->db->ExecuteSelectQueryAndFetchAll($sql, $params);

         // Use text filter to filter text to format links and text
         if($useFilter)
         {
            foreach ($result as $key => $value) 
             {
                 $value->data = $this->textFilter->doFilter(htmlentities($value->data), $value->filter);
                 $value->title = htmlentities($value->title);
                 $value->plot = htmlentities($value->plot);
             }
         }

         return $result;
     } 

      /************************************************************************** 
  * getRowCount, fetch number of rows in table.
  * @param -
  * @return the number of rows in table
  */

  public function getRowCount()
  {
    $view = $this->createView();
    $sqlBase = "SELECT * FROM $view";
    $sql = "SELECT COUNT(id) AS rows FROM ($sqlBase)\n AS Movies";
    return $this->db->executeSelectQueryAndFetchAll($sql)[0]->rows;
  } 

} 

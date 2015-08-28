 <?php

class CBlog extends CContent {

    public function __construct($database) {
        parent::__construct($database);
    }

 /**
   * Sanitize content before using it.
   *
   * @param $c Den data som ska visas går igon och valideras för att 
   *        kontrollera htmlEntities och liknande skadlig kod.
   */
    public function sanitizeVariables($c) {
        parent::sanitizeVariables($c);
        $filter = new CTextFilter(); 
        $this->title  = htmlentities($c->title, null, 'UTF-8');
        $this->data   = $filter->doFilter(htmlentities($c->DATA, null, 'UTF-8'), $c->FILTER);
    }
    
    
 /**
   * getPosts hämtar alla inlägg i databasen.
   *
   * @param 
   *
   */
    public function getPosts() {        
        $this->slug = isset($_GET['slug']) ? $_GET['slug'] : null;
        $slugSql = $this->slug ? 'slug = ?' : '1';
        $sql = "
            SELECT *
            FROM Content
            WHERE
              type = 'post' AND
              $slugSql AND
              published <= NOW()
            ORDER BY updated DESC
            ;
            ";

        $res = $this->db->ExecuteSelectQueryAndFetchAll($sql, array($this->slug));

        return $res; 
    }


 /**
   * viewsCount uppdaterar antalet views på varje table row som varje unik post har. 
   *
   * @param id på den table row som ska visas i bloggen
   *        
   */
    public function viewsCount($c){
        $sample_rate = 1;
        $sql = "UPDATE Content SET views = views + $sample_rate WHERE id = '$c' ";
        $res = $this->db->ExecuteSelectQueryAndFetchAll($sql, array($c));
        return $res; 
     }


 /** Visar den data som söks efter
   * 
   *
   * @param $c, väljer ut data från db via id:et
   *         
   */
    public function renderSearchHTML($c) {
        $html ="<tr class='table table-list-search'>
                <td><a href='blog.php?slug={$this->slug}'>{$this->title}</a></td>
                <td>{$this->getCategories($c->id)}</td>
                <td>{$c->updated} </td>
                <td>{$c->user} </td>
                <td>{$c->views} </td>
                </tr>";
        return $html;
    }


 /**
   * Render all blogs 
   *
   * @param id av content
   *         
   */
    public function renderHTML($c) {
        $this->viewsCount($c->id);
        $this->sanitizeVariables($c); 
        $editLink = $this->isAdmin ? "<a href='editController.php?id={$c->id}'>Uppdatera</a>" : null;
        $createLink = $this->isAdmin ? "<a href='createController.php'>Skapa</a>" : null;
        $deleteLink = $this->isAdmin ? "<a href='deleteController.php?id={$c->id}'>Delete</a>" : null;
        $html ="
        <div class='container'>
            <div class='panel panel-info'>
                <div class='panel-heading'>
                    <!-- Title -->
                    <ul class='breadcrumb'>
                        <li><a href='viewblogs.php'>View All</a></li>
                        <li class='active'>{$this->title}</li>
                    </ul>
                   <h2><a href='blog.php?slug={$this->slug}'>{$this->title}</a></h2>

                    <div style='padding-top:30px' class='panel-body'>

                        <!-- Author -->
                        <p class='lead'>
                           {$this->data}
                        </p>

                        <p> 
                            {$this->getCategories($c->id)}
                            <br>
                              <i class='glyphicon glyphicon-pencil'>{$editLink} </i>
                            | <i class='glyphicon glyphicon-remove'>{$deleteLink} </i> 
                            <br>
                            | <i class='glyphicon glyphicon-time'></i> {$c->updated}
                            | <i class='glyphicon glyphicon-user'></i> {$c->user} |
                              <i class='glyphicon glyphicon-eye-open'></i> <a href='#'> {$c->views}</a>
                        </p>
                    </div>                     
                </div>  
            </div>  
        </div>";
        return $html;
    }

 /**
   * Show first sentence of the blog content
   *
   * @param whole blog data, strips down to first sentence
   *         
   */
    public function first_sentence($content) {
        $pos = strpos($content, '.');
        return substr($content, 0, $pos+1);
    }

 /**
   * Get Blogs 
   *
   * @param 
   *         
   */
    public function renderBlogs($c) {
        $this->sanitizeVariables($c); 
        $html ="
        <div class='container'>
            <div class='panel panel-info'>
                <div class='panel-heading'>
                    <!-- Title -->
                    <h2><a href='blog.php?slug={$this->slug}'>{$this->title}</a></h2>
                    <div style='padding-top:30px' class='panel-body'>
                    <div class='breadcrumb'>
                        <p>
                            {$this->first_sentence($this->data)}..
                            <a href='blog.php?slug={$this->slug}'> <b>Read more..</b> </a>
                        </p>
                        <p> 
                            {$this->getCategories($c->id)}
                            <br>
                              <i class='glyphicon glyphicon-time'></i> {$c->updated} 
                            | <i class='glyphicon glyphicon-user'></i> {$c->user} 
                            | <i class='glyphicon glyphicon-eye-open'></i> <a href='#'> {$c->views}</a>
                        </p>
                    </div>                     
                </div>  
            </div>  
        </div>";
        return $html;
    }
        
   
     /**
    * Get insert form 
    *
    * @param 
    *         
    */
    public function renderInsertForm($output) {
        $html = null;
        $html .= "<form method='POST' enctype='multipart/form-data' >";

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
        $html .= $this->categories();
        $html .="</div>";

        $html .= "<p class=buttons><input type='submit' name='save' value='Skapa'/></p>";
        $html .= "<p><a href='blog.php'>Visa alla</a></p>";
        $html .= "<output>{$output}</output>";

        $html .= "</form>";
        return $html;
    }

 /**
   * Get Categories 
   *
   * @param
   *         
   */
    public function getCategories($c){
        $html = null;
        $sql = "SELECT name FROM Categories 
                INNER JOIN Content2Categories
                ON Categories.id = Content2Categories.idCategories
                inner join Content
                ON Content.id = Content2Categories.idContent
                where Content.id = ? ";

        $res = $this->db->ExecuteSelectQueryAndFetchAll($sql, array($c));
        #echo "$sql";
        foreach ($res as $value) {
            $html .= "<a href='blog-genre.php?name={$value->name}'><span class='label label-info'> {$value->name}</span></a> ";
        }
        return $html; 
    }

 /**
   * Get Categories 
   *
   * @param
   *         
   */
    public function categories(){
        $html = null;
        $sql = "SELECT name, count(Categories.id) AS antal
                FROM Categories
                INNER JOIN Content2Categories
                ON Categories.id = Content2Categories.idCategories
                inner join Content
                ON Content.id = Content2Categories.idContent
                group by Categories.name";

        $res = $this->db->ExecuteSelectQueryAndFetchAll($sql);
        #echo "$sql";
        foreach ($res as $value) {
            $html .= "<a href='blog-genre.php?name={$value->name}'><span class='label label-info'> {$value->name} ({$value->antal})</span></a> ";
        }
        return $html; 
    }


 /**
   * Get Categories 
   *
   * @param $name id of categories to show
   *         
   */
    public function getCategorie($name) {
        $this->slug = isset($_GET['slug']) ? $_GET['slug'] : null;
        $id = $this->getGenreId($name);
        $slugSql = $this->slug ? 'slug = ?' : '1';
        $sql = "
            SELECT *
            FROM Categories
            INNER JOIN Content2Categories
            ON Categories.id = Content2Categories.idCategories
            inner join Content
            ON Content.id = Content2Categories.idContent
            where Categories.id = ?;";

        $res = $this->db->ExecuteSelectQueryAndFetchAll($sql, array($id));
        return $res; 
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




    public function getTitle() {
        return $this->title; 
    }
    
    public function getSlug() {
        return $this->slug; 
    }
    

}

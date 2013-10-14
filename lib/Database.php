<?
class Database {
    public function __construct() {
        if(!isset($_SESSION['db'])) {
            @$_SESSION['db'] = new mysqli("localhost", "root", "", "medior");
            
            if (mysqli_connect_errno()) {
                printf("MySQL Error: %s\n", mysqli_connect_error());
                exit();
            }
        }
    }
    
    public function DB() {
        return $_SESSION['db'];
    }
}
?>
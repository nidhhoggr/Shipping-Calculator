<?php 
if(!empty($_GET)) {
    require_once('ServiceClass.php');

    $args = array(
                  'item'=>array('qty'=>$_GET['qty'],'weight'=>$_GET['weight']),
                  'recip'=>array('zip'=>$_GET['zip'])
                 );

    $s = new Service($args);

    $rate = $s->getRate();
    $service = $s->getService();

    echo "Rate: $rate, Service: $service";
}                                
?>
<form method="get" action="<?=$_SERVER['PHP_SELF']?>">
    Qty<input type="text" name="qty" value="<?=$_GET['qty']?>" />
    <br />
    Weight<input type="text" name="weight" value="<?=$_GET['weight']?>" />
    <br />
    Zip<input type="text" name="zip" value="<?=$_GET['zip']?>" />
    <br />
    <input type="submit" value="estimate" />
</form>

<?php
include('Avalidator.php');
$validation = new Avalidator();

if($_POST)
{
    $text = 'Astazi mergem la plimbare';

    $validation->field('name','_POST','trim|required<*Why didn\'t you fill the name field?*>|alphanumeric','Nume');
    $validation->field('favFruit','_POST','trim|required|alpha','Favorite fruit');
    $validation->field('text',$text,'trim|required','Text');
    $validation->field('email','_POST','trim|required|email');
    $validation->field('number','_POST','trim|required|integer[>=5&<98]','Number');
    if($validation->validate())
    {
        echo 'Validation passed';
    }
    else {
        $errors = $validation->errors();
        echo '<br /><br /><br />****************<br />';
        print_r($errors);
        echo '<br /><br /><br />****************<br />';
        print_r($validation->errors('favFruit'));
        echo '<br /><br /><br />****************<br />';
        print_r($validation->get_value('favFruit', 1, 'array'));
    }
}
?>

<form id="contact_form" action="testing.php" method="post">
    <label>Name: <input class="textfield" name="name" type="text" value="<?php echo $validation->get_value('name');?>" /></label>
    <label>Email: <input class="textfield" name="email" type="text" value="<?php echo $validation->get_value('email');?>" /></label>
    <label>Number: <input class="textfield" name="number" type="text" value="<?php echo $validation->get_value('number');?>" /></label>
    <label>Message: <textarea class="textarea" cols="45" name="message" rows="5"></textarea></label> 
    <select name="favFruit[]" size="4" multiple>
        <?php
        $options = array(
            'apple'=>'Apple',
            'banana'=>'Ban ana',
            'plum'=>'Plum',
            'pomegranate'=>'Pomegranate',
            'strawberry'=>'Strawberry',
            'watermelon'=>'Watermelon');
        $selected = $validation->get_value('favFruit',1,'array');
        print_r($selected);
        foreach($options as $name=>$value)
        {
            echo '<option value="'.$name.'"';
            if(in_array($name,$selected)) echo ' selected';
            echo '>'.$value.'</option>';
        }
        ?>
</select>
    <input class="button" name="submit" type="submit" value="Submit" />
</form>
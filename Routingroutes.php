<?php
use Helpers\ValidationHelper;
use Response\HTTPRenderer;
use Response\Render\HTMLRenderer;
use Database\DataAccess\Implementations\ComputerPartDAOImpl;

return [
    'random/part'=>function(): HTTPRenderer{
        $partDao = new ComputerPartDAOImpl();
        $part = $partDao->getRandom();

        if($part === null) throw new Exception('No parts are available!');

        return new HTMLRenderer('component/computer-part-card', ['part'=>$part]);
    },
    'parts'=>function(): HTTPRenderer{
        // IDの検証
        $id = ValidationHelper::integer($_GET['id']??null);

        $partDao = new ComputerPartDAOImpl();
        $part = $partDao->getById($id);

        if($part === null) throw new Exception('Specified part was not found!');

        return new HTMLRenderer('component/computer-part-card', ['part'=>$part]);
    }
];

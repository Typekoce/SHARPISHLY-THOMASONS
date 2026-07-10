package App;

import Controllers.Controller;
import Models.Model;
import Views.View;

public class App {
    public static void main(String[] args) {
        Model model = new Model();
        View view = new View();
        Controller controller = new Controller(model, view);

        controller.execute();
    }
}
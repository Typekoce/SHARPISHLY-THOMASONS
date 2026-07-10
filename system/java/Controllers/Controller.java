package Controllers;

import Models.Model;
import Views.View;

public class Controller {
    private final Model model;
    private final View view;

    public Controller(Model model, View view) {
        this.model = model;
        this.view = view;
    }

    public void execute() {
        view.setData(model.getData());
        view.render();
    }
}
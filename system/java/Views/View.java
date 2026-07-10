package Views;

public class View {
    private String data;

    public void setData(String data) {
        this.data = data;
    }

    public void render() {
        System.out.println("View rendering: " + data);
    }
}
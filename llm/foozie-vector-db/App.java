import java.util.Arrays;
public class App {
    public static void main(String[] args) {
        Controller controller = new Controller("data/vectors.db");

        if (args.length == 0) {
            controller.runDemo();
            return;
        }

        // Simple CLI Protocol: java App add [id] [vector_csv] [metadata]
        String action = args[0];
        if (action.equals("add") && args.length >= 4) {
            String id = args[1];
            String[] vStr = args[2].split(",");
            double[] vector = Arrays.stream(vStr).mapToDouble(Double::parseDouble).toArray();
            String metadata = args[3];
            
            // Note: Model already has 'synchronized add'
            new Model("data/vectors.db").add(id, vector, metadata);
            System.out.println("SUCCESS");
        }
    }
}
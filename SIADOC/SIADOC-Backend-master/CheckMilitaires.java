import java.sql.*;

public class CheckMilitaires {
    public static void main(String[] args) {
        try {
            Connection conn = DriverManager.getConnection("jdbc:postgresql://localhost:5432/drh_militaire", "postgres", "postgres");
            Statement stmt = conn.createStatement();
            ResultSet rs = stmt.executeQuery("SELECT count(*) FROM militaire");
            if (rs.next()) {
                System.out.println("======> Militaires in DB : " + rs.getInt(1));
            }
            conn.close();
        } catch (Exception e) {
            e.printStackTrace();
        }
    }
}
